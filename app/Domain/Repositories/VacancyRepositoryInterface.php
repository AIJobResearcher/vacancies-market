<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\DTOs\GetVacanciesByJobIdFilterDto;
use App\Domain\Entities\Vacancy;
use App\Domain\ValueObjects\EntityIds\VacancyId;

interface VacancyRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function findById(VacancyId $id): ?Vacancy;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function save(Vacancy $vacancy): void;

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     * }
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function searchPreviews(
        GetVacanciesByJobIdFilterDto $filter,
        int $page,
        int $perPage,
    ): array;

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     employer_id: string,
     *     employer_title: string,
     *     min_salary: int,
     *     max_salary: int|null,
     *     country: string|null,
     *     city: string|null,
     *     employment_type: string,
     *     workplace: string,
     *     status: string,
     *     posted_at: string,
     *     description: string|null,
     *     requirements: list<string>,
     *     internal_url: string|null,
     *     external_urls: string[],
     *     employer: array{
     *         id: string,
     *         title: string,
     *         description: string|null,
     *         website: string|null,
     *         email: string|null,
     *         phone: string|null,
     *         logo_url: string|null,
     *     },
     *     interviewer: array{
     *         id: string,
     *         full_name: string,
     *         position: string|null,
     *         profile_urls: array<string, string>|null,
     *     }|null,
     *     closed_at: string|null,
     *     created_at: string,
     *     updated_at: string,
     *     version: int,
     * }|null
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function findDetailById(VacancyId $id): ?array;
}
