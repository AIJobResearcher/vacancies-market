<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Job;
use App\Domain\ValueObjects\EntityIds\JobId;

/** @psalm-suppress UnusedClass */
interface JobRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function findById(JobId $id): ?Job;

    /**
     * @param list<string> $ids
     * @return array{
     *     items: array<int, array{
     *         id: string,
     *         title: string,
     *         category: string,
     *         sub_category: string|null,
     *         parent_job_id: string|null,
     *         parent_job_title: string|null,
     *     }>,
     *     total: int,
     * }
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function findPreviewsByIds(array $ids): array;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function save(Job $job): void;
}
