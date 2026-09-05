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

    /** @psalm-suppress PossiblyUnusedMethod */
    public function save(Job $job): void;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function hasActiveVacancyAssignments(JobId $jobId): bool;
}
