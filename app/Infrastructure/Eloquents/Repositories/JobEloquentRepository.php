<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Entities\Job;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\JobRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Infrastructure\Eloquents\Mappers\JobMapper;
use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\JobRequirementModel;
use App\Infrastructure\Eloquents\Models\VacancyJobAssignmentModel;
use Illuminate\Support\Facades\DB;
use Override;

final class JobEloquentRepository implements JobRepositoryInterface
{
    public function __construct(private readonly JobMapper $mapper)
    {
    }

    #[Override]
    public function findById(JobId $id): ?Job
    {
        $model = JobModel::query()
            ->with('requirements')
            ->find($id->value());

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    #[Override]
    public function save(Job $job): void
    {
        DB::transaction(function () use ($job): void {
            $this->persistRoot($job);
            $this->reconcileRequirements($job);
        });
    }

    #[Override]
    public function hasActiveVacancyAssignments(JobId $jobId): bool
    {
        return VacancyJobAssignmentModel::query()
            ->where('job_id', $jobId->value())
            ->whereNull('unassigned_at')
            ->exists();
    }

    private function persistRoot(Job $job): void
    {
        $state = $this->mapper->toPersistenceState($job);

        if (JobModel::query()->whereKey($job->id()->value())->exists()) {
            $expected = $job->version() - 1;
            $affected = JobModel::query()
                ->whereKey($job->id()->value())
                ->where('version', $expected)
                ->update($state);

            if ($affected === 0) {
                $existing = JobModel::query()
                    ->whereKey($job->id()->value())
                    ->first(['version']);

                $actual = $existing === null ? 0 : $existing->version;

                throw new VersionConflictException(
                    'Job',
                    $job->id()->value(),
                    $expected,
                    $actual,
                );
            }
        } else {
            JobModel::query()->create($state);
        }
    }

    private function reconcileRequirements(Job $job): void
    {
        $jobId = $job->id()->value();
        $snapshot = array_map(
            static fn ($requirementId): string => $requirementId->value(),
            $job->requirementIds(),
        );

        JobRequirementModel::query()
            ->where('job_id', $jobId)
            ->whereNotIn('requirement_id', $snapshot)
            ->delete();

        foreach ($snapshot as $requirementId) {
            JobRequirementModel::query()->updateOrCreate(
                ['job_id' => $jobId, 'requirement_id' => $requirementId],
                ['job_id' => $jobId, 'requirement_id' => $requirementId],
            );
        }
    }
}
