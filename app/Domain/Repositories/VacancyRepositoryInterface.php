<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Vacancy;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\VacancyId;

interface VacancyRepositoryInterface
{
    public function findById(VacancyId $id): ?Vacancy;
    public function save(Vacancy $vacancy): void;

    /** @return Vacancy[] */
    public function findActiveByJobId(JobId $jobId): array;
}
