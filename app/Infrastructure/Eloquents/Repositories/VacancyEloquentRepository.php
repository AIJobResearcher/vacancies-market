<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Entities\Vacancy;
use App\Domain\Entities\VacancyRequirementAssignment;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\VacancySearchCriteria;
use App\Infrastructure\Eloquents\Mappers\VacancyMapper;
use App\Infrastructure\Eloquents\Models\EmployerModel;
use App\Infrastructure\Eloquents\Models\InterviewerModel;
use App\Infrastructure\Eloquents\Models\OutboxMessageModel;
use App\Infrastructure\Eloquents\Models\RequirementModel;
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

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     * }
     */
    #[Override]
    public function searchPreviews(
        VacancySearchCriteria $criteria,
        int $page,
        int $perPage,
    ): array {
        $query = VacancyModel::query()
            ->join('employers', 'employers.id', '=', 'vacancies.employer_id')
            ->select(self::PREVIEW_COLUMNS);

        $this->applyPreviewFilters($query, $criteria);

        $paginator = $query
            ->orderByDesc('vacancies.posted_at')
            ->orderBy('vacancies.id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => array_values(
                $paginator->getCollection()
                    ->map(static fn (VacancyModel $vacancy): array => $vacancy->toArray())
                    ->all()
            ),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     employer_id: string,
     *     employer_title: string,
     *     min_salary: int,
     *     max_salary: int|null,
     *     country: string|null,
     *     city: string|null,
     *     employment_type: string,
     *     workplace: string,
     *     status: string,
     *     posted_at: string,
     *     description: string|null,
     *     requirements: list<string>,
     *     internal_url: string|null,
     *     external_urls: string[],
     *     employer: array{
     *         id: string,
     *         title: string,
     *         description: string|null,
     *         website: string|null,
     *         email: string|null,
     *         phone: string|null,
     *         logo_url: string|null,
     *     },
     *     interviewer: array{
     *         id: string,
     *         full_name: string,
     *         position: string|null,
     *         profile_urls: array<string, string>|null,
     *     }|null,
     *     closed_at: string|null,
     *     created_at: string,
     *     updated_at: string,
     *     version: int,
     * }|null
     */
    #[Override]
    public function findDetailById(VacancyId $id): ?array
    {
        $vacancy = $this->findById($id);

        if ($vacancy === null) {
            return null;
        }

        $employer = EmployerModel::query()->findOrFail($vacancy->employerId()->value());
        $interviewer = $this->findActiveInterviewer($id->value());

        return [
            'id' => $vacancy->id()->value(),
            'title' => $vacancy->title(),
            'employer_id' => $vacancy->employerId()->value(),
            'employer_title' => $employer->title,
            'min_salary' => $vacancy->salary()->min(),
            'max_salary' => $vacancy->salary()->max(),
            'country' => $vacancy->country(),
            'city' => $vacancy->city(),
            'employment_type' => $vacancy->employmentType()->value,
            'workplace' => $vacancy->workplace()->value,
            'status' => $vacancy->status(),
            'posted_at' => $vacancy->postedAt()->format(DATE_ATOM),
            'description' => $vacancy->description(),
            'requirements' => $this->findRequirementTitles($vacancy->requirementAssignments()),
            'internal_url' => $vacancy->internalUrl(),
            'external_urls' => $vacancy->externalUrls()->toArray(),
            'employer' => [
                'id' => $employer->id,
                'title' => $employer->title,
                'description' => $employer->description,
                'website' => $employer->website,
                'email' => $employer->email,
                'phone' => $employer->phone,
                'logo_url' => $employer->logo_url,
            ],
            'interviewer' => $interviewer === null ? null : [
                'id' => $interviewer->id,
                'full_name' => $interviewer->full_name,
                'position' => $interviewer->position,
                'profile_urls' => $interviewer->profile_urls,
            ],
            'closed_at' => $vacancy->closedAt()?->format(DATE_ATOM),
            'created_at' => $vacancy->createdAt()->format(DATE_ATOM),
            'updated_at' => $vacancy->updatedAt()->format(DATE_ATOM),
            'version' => $vacancy->version(),
        ];
    }

    private function findActiveInterviewer(string $vacancyId): ?InterviewerModel
    {
        return InterviewerModel::query()
            ->join(
                'interviewer_vacancy_assignments',
                'interviewer_vacancy_assignments.interviewer_id',
                '=',
                'interviewers.id'
            )
            ->where('interviewer_vacancy_assignments.vacancy_id', $vacancyId)
            ->whereNull('interviewer_vacancy_assignments.unassigned_at')
            ->orderByDesc('interviewer_vacancy_assignments.assigned_at')
            ->first(['interviewers.*']);
    }

    /**
     * @param VacancyRequirementAssignment[] $assignments
     * @return list<string>
     */
    private function findRequirementTitles(array $assignments): array
    {
        $requirementIds = [];
        foreach ($assignments as $assignment) {
            $requirementIds[] = $assignment->getRequirementId()->value();
        }

        $requirements = RequirementModel::query()
            ->whereIn('id', $requirementIds)
            ->orderBy('title')
            ->get();

        $titles = [];
        foreach ($requirements as $requirement) {
            $titles[] = $requirement->title;
        }

        return $titles;
    }

    /** @param Builder<VacancyModel> $query */
    private function applyPreviewFilters(Builder $query, VacancySearchCriteria $criteria): void
    {
        $query
            ->join(
                'vacancy_job_assignments',
                'vacancy_job_assignments.vacancy_id',
                '=',
                'vacancies.id'
            )
            ->where('vacancy_job_assignments.job_id', $criteria->jobId->value())
            ->whereNull('vacancy_job_assignments.unassigned_at');

        if ($criteria->employerId !== null) {
            $query->where('vacancies.employer_id', $criteria->employerId->value());
        }

        if ($criteria->country !== null) {
            $query->where('vacancies.country', $criteria->country);
        }

        if ($criteria->city !== null) {
            $query->where('vacancies.city', $criteria->city);
        }

        if ($criteria->minSalary !== null) {
            $query->where('vacancies.min_salary', '>=', $criteria->minSalary);
        }

        if ($criteria->maxSalary !== null) {
            $query->where('vacancies.max_salary', '<=', $criteria->maxSalary);
        }

        if ($criteria->status !== null) {
            $query->where('vacancies.status', $criteria->status->value);
        }

        if ($criteria->workplace !== null) {
            $query->where('vacancies.workplace', $criteria->workplace->value);
        }

        if ($criteria->employmentType !== null) {
            $query->where('vacancies.employment_type', $criteria->employmentType->value);
        }

        if ($criteria->postedFrom !== null) {
            $query->where('vacancies.posted_at', '>=', $criteria->postedFrom);
        }

        if ($criteria->postedTo !== null) {
            $query->where('vacancies.posted_at', '<=', $criteria->postedTo);
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
