<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\DTOs\JobPreviewDto;
use App\Domain\Entities\Job;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\JobRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Infrastructure\Eloquents\Mappers\JobMapper;
use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\JobRequirementModel;
use Illuminate\Support\Facades\DB;
use Override;

final class JobEloquentRepository implements JobRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
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

    /**
     * @param list<string> $ids
     * @return list<JobPreviewDto>
     */
    #[Override]
    public function findPreviewsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $query = JobModel::query();

        $query->leftJoin(
            'jobs as parent_jobs',
            'parent_jobs.id',
            '=',
            'jobs.parent_job_id'
        );

        $query->whereIn('jobs.id', $ids)
            ->whereNull('jobs.deleted_at');

        $jobs = $query->get([
            'jobs.id',
            'jobs.title',
            'jobs.category',
            'jobs.sub_category',
            'jobs.parent_job_id',
            'parent_jobs.title as parent_job_title',
        ]);

        $previews = [];

        foreach ($jobs as $job) {
            $previews[] = $this->mapper->toPreviewDto($job);
        }

        return $previews;
    }

    #[Override]
    public function save(Job $job): void
    {
        DB::transaction(function () use ($job): void {
            $this->persistRoot($job);
            $this->reconcileRequirements($job);
        });
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
