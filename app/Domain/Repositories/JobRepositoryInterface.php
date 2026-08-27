<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Job;

interface JobRepositoryInterface
{
    public function findById(string $id): ?Job;
    public function save(Job $job): void;
    public function hasActiveVacancyAssignments(string $jobId): bool;
}