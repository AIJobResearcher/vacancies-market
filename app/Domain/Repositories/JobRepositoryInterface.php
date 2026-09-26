<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\DTOs\JobPreviewDto;
use App\Domain\Entities\Job;
use App\Domain\ValueObjects\EntityIds\JobId;

/** @psalm-suppress UnusedClass */
interface JobRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function findById(JobId $id): ?Job;

    /**
     * @param list<string> $ids
     * @return array<int, JobPreviewDto>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function findPreviewsByIds(array $ids): array;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function save(Job $job): void;
}
