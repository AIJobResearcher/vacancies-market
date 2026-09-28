<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\DTOs\JobPreviewPageDto;
use App\Domain\Repositories\JobRepositoryInterface;
use App\Infrastructure\Eloquents\Mappers\JobMapper;
use App\Infrastructure\Eloquents\Models\JobModel;
use Override;

final class JobEloquentRepository implements JobRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private readonly JobMapper $mapper)
    {
    }

    /**
     * @param list<string> $ids
     */
    #[Override]
    public function findPreviewsByIds(array $ids): JobPreviewPageDto
    {
        if ($ids === []) {
            return new JobPreviewPageDto([], 0);
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

        return new JobPreviewPageDto($previews, count($previews));
    }
}
