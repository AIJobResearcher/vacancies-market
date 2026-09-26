<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\DTOs\GetVacanciesByJobIdFilterDto;
use App\Domain\DTOs\VacancyDetailDto;
use App\Domain\DTOs\VacancyPreviewPageDto;
use App\Domain\Entities\Vacancy;
use App\Domain\ValueObjects\EntityIds\VacancyId;

interface VacancyRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function findById(VacancyId $id): ?Vacancy;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function save(Vacancy $vacancy): void;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function searchPreviews(
        GetVacanciesByJobIdFilterDto $filter,
        int $page,
        int $perPage,
    ): VacancyPreviewPageDto;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function findDetailById(VacancyId $id): ?VacancyDetailDto;
}
