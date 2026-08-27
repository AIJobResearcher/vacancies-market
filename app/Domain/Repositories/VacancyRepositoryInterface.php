<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Vacancy;

interface VacancyRepositoryInterface
{
    public function findById(string $id): ?Vacancy;
    public function save(Vacancy $vacancy): void;
    public function findActiveByJobId(string $jobId): array;
}