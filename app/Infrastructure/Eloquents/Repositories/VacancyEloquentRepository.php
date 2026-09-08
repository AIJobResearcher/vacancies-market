<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Entities\Vacancy;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Infrastructure\Eloquents\Mappers\VacancyMapper;
use App\Infrastructure\Eloquents\Models\OutboxMessageModel;
use App\Infrastructure\Eloquents\Models\VacancyJobAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use App\Infrastructure\Eloquents\Models\VacancyRequirementAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancySourceModel;
use Illuminate\Support\Facades\DB;
use Override;

final class VacancyEloquentRepository implements VacancyRepositoryInterface
{
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

    /** @return Vacancy[] */
    #[Override]
    public function findActiveByJobId(JobId $jobId): array
    {
        $vacancyIds = VacancyJobAssignmentModel::query()
            ->where('job_id', $jobId->value())
            ->whereNull('unassigned_at')
            ->pluck('vacancy_id');

        if ($vacancyIds->isEmpty()) {
            return [];
        }

        $models = VacancyModel::query()
            ->with(['requirementAssignments', 'jobAssignments', 'sources'])
            ->whereIn('id', $vacancyIds)
            ->where('status', 'open')
            ->get();

        $vacancies = [];
        foreach ($models as $model) {
            $vacancies[] = $this->mapper->toDomain($model);
        }

        return $vacancies;
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
