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
     *     items: list<array<string, mixed>>,
     *     total: int,
     * }
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function findPreviewsByIds(array $ids): array;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function save(Job $job): void;
}
