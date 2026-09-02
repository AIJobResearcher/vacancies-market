<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Job;
use App\Domain\ValueObjects\EntityIds\JobId;

interface JobRepositoryInterface
{
    public function findById(JobId $id): ?Job;
    public function save(Job $job): void;
    public function hasActiveVacancyAssignments(JobId $jobId): bool;
}