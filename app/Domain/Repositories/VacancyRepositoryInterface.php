<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Vacancy;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\VacancyId;

interface VacancyRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function findById(VacancyId $id): ?Vacancy;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function save(Vacancy $vacancy): void;

    /**
     * @return Vacancy[]
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function findActiveByJobId(JobId $jobId): array;
}
