<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\DTOs\GetVacanciesByJobIdFilterDto;
use App\Domain\DTOs\VacancyDetailDto;
use App\Domain\DTOs\VacancyPreviewPageDto;
use App\Domain\Entities\Vacancy;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Infrastructure\Eloquents\Mappers\VacancyMapper;
use App\Infrastructure\Eloquents\Models\OutboxMessageModel;
use App\Infrastructure\Eloquents\Models\VacancyJobAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use App\Infrastructure\Eloquents\Models\VacancyRequirementAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancySourceModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Override;

final class VacancyEloquentRepository implements VacancyRepositoryInterface
{
    private const PREVIEW_COLUMNS = [
        'vacancies.id',
        'vacancies.title',
        'vacancies.employer_id',
        'employers.title as employer_title',
        'vacancies.min_salary',
        'vacancies.max_salary',
        'vacancies.country',
        'vacancies.city',
        'vacancies.employment_type',
        'vacancies.workplace',
        'vacancies.status',
        'vacancies.posted_at',
    ];

    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private readonly VacancyMapper $mapper)
    {
    }

    #[Override]
    public function findById(VacancyId $id): ?Vacancy
    {
        $model = VacancyModel::query()
            ->with(['requirementAssignments', 'jobAssignments', 'sources'])
            ->find($id->value());

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    #[Override]
    public function save(Vacancy $vacancy): void
    {
        DB::transaction(function () use ($vacancy): void {
            $events = $vacancy->releaseEvents();

            $this->persistRoot($vacancy);

            $this->reconcileRequirementAssignments($vacancy);
            $this->reconcileJobAssignments($vacancy);
            $this->reconcileSources($vacancy);

            foreach ($events as $event) {
                OutboxMessageModel::query()->create([
                    'event_id' => $event->eventId,
                    'event_type' => $event->eventType,
                    'payload' => json_encode($event, JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }

    #[Override]
    public function searchPreviews(
        GetVacanciesByJobIdFilterDto $filter,
        int $page,
        int $perPage,
    ): VacancyPreviewPageDto {
        $query = VacancyModel::query();

        $this->applyPreviewFilters($query, $filter);

        $query->join('employers', 'employers.id', '=', 'vacancies.employer_id')
            ->select(self::PREVIEW_COLUMNS);

        $query->orderByDesc('vacancies.posted_at')
            ->orderBy('vacancies.id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];

        foreach ($paginator->getCollection() as $vacancy) {
            $items[] = $this->mapper->toPreviewDto($vacancy);
        }

        return new VacancyPreviewPageDto($items, $paginator->total());
    }

    #[Override]
    public function findDetailById(VacancyId $id): ?VacancyDetailDto
    {
        $vacancy = VacancyModel::query()
            ->with(['employer', 'requirements', 'activeAssignment.interviewer'])
            ->find($id->value());

        return $vacancy === null ? null : $this->mapper->toDetailDto($vacancy);
    }

    /** @param Builder<VacancyModel> $query */
    private function applyPreviewFilters(Builder $query, GetVacanciesByJobIdFilterDto $filter): void
    {
        $query
            ->join(
                'vacancy_job_assignments',
                'vacancy_job_assignments.vacancy_id',
                '=',
                'vacancies.id'
            )
            ->where('vacancy_job_assignments.job_id', $filter->jobId->value())
            ->whereNull('vacancy_job_assignments.unassigned_at');

        if ($filter->employerId !== null) {
            $query->where('vacancies.employer_id', $filter->employerId->value());
        }

        if ($filter->country !== null) {
            $query->where('vacancies.country', $filter->country);
        }

        if ($filter->city !== null) {
            $query->where('vacancies.city', $filter->city);
        }

        if ($filter->minSalary !== null) {
            $query->where('vacancies.min_salary', '>=', $filter->minSalary);
        }

        if ($filter->maxSalary !== null) {
            $query->where('vacancies.max_salary', '<=', $filter->maxSalary);
        }

        if ($filter->status !== null) {
            $query->where('vacancies.status', $filter->status->value);
        }

        if ($filter->workplace !== null) {
            $query->where('vacancies.workplace', $filter->workplace->value);
        }

        if ($filter->employmentType !== null) {
            $query->where('vacancies.employment_type', $filter->employmentType->value);
        }

        if ($filter->postedFrom !== null) {
            $query->where('vacancies.posted_at', '>=', $filter->postedFrom);
        }

        if ($filter->postedTo !== null) {
            $query->where('vacancies.posted_at', '<=', $filter->postedTo);
        }
    }

    private function persistRoot(Vacancy $vacancy): void
    {
        $state = $this->mapper->toPersistenceState($vacancy);

        if (VacancyModel::query()->whereKey($vacancy->id()->value())->exists()) {
            $expected = $vacancy->version() - 1;
            $affected = VacancyModel::query()
                ->whereKey($vacancy->id()->value())
                ->where('version', $expected)
                ->update($state);

            if ($affected === 0) {
                $existing = VacancyModel::query()
                    ->whereKey($vacancy->id()->value())
                    ->first(['version']);

                $actual = $existing === null ? 0 : $existing->version;

                throw new VersionConflictException(
                    'Vacancy',
                    $vacancy->id()->value(),
                    $expected,
                    $actual,
                );
            }
        } else {
            VacancyModel::query()->create($state);
        }
    }

    private function reconcileRequirementAssignments(Vacancy $vacancy): void
    {
        $vacancyId = $vacancy->id()->value();
        $snapshot = $vacancy->requirementAssignments();

        VacancyRequirementAssignmentModel::query()
            ->where('vacancy_id', $vacancyId)
            ->whereNotIn('id', array_map(fn ($a): string => $a->id()->value(), $snapshot))
            ->delete();

        foreach ($snapshot as $assignment) {
            $values = [
                'vacancy_id' => $assignment->vacancyId()->value(),
                'requirement_id' => $assignment->getRequirementId()->value(),
                'assigned_at' => $assignment->assignedAt(),
                'version' => $assignment->version(),
            ];

            VacancyRequirementAssignmentModel::query()->updateOrCreate(
                ['id' => $assignment->id()->value()],
                $values
            );
        }
    }

    private function reconcileJobAssignments(Vacancy $vacancy): void
    {
        $vacancyId = $vacancy->id()->value();
        $snapshot = $vacancy->jobAssignments();

        VacancyJobAssignmentModel::query()
            ->where('vacancy_id', $vacancyId)
            ->whereNotIn('id', array_map(fn ($a): string => $a->id()->value(), $snapshot))
            ->delete();

        foreach ($snapshot as $assignment) {
            $values = [
                'vacancy_id' => $assignment->vacancyId()->value(),
                'job_id' => $assignment->jobId()->value(),
                'assigned_at' => $assignment->assignedAt(),
                'unassigned_at' => $assignment->unassignedAt(),
                'relevance_score' => $assignment->relevanceScore(),
                'version' => $assignment->version(),
            ];

            VacancyJobAssignmentModel::query()->updateOrCreate(
                ['id' => $assignment->id()->value()],
                $values
            );
        }
    }

    private function reconcileSources(Vacancy $vacancy): void
    {
        $vacancyId = $vacancy->id()->value();
        $snapshot = $vacancy->sources();

        VacancySourceModel::query()
            ->where('vacancy_id', $vacancyId)
            ->whereNotIn('id', array_map(fn ($s): string => $s->id()->value(), $snapshot))
            ->delete();

        foreach ($snapshot as $source) {
            $values = [
                'vacancy_id' => $source->vacancyId()->value(),
                'source_key' => $source->sourceKey(),
                'external_vacancy_id' => $source->externalVacancyId(),
                'external_url' => $source->externalUrl(),
                'first_seen_at' => $source->firstSeenAt(),
                'last_seen_at' => $source->lastSeenAt(),
                'closed_at' => $source->closedAt(),
                'is_primary' => $source->isPrimary(),
            ];

            VacancySourceModel::query()->updateOrCreate(
                ['id' => $source->id()->value()],
                $values
            );
        }
    }
}
