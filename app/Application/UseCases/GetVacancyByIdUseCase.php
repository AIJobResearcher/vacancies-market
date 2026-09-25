<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Exceptions\EntityNotFoundException\VacancyNotFoundException;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\VacancyId;

final class GetVacancyByIdUseCase
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private readonly VacancyRepositoryInterface $vacancyRepository)
    {
    }

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
     * }
     */
    public function handle(string $id): array
    {
        $vacancy = $this->vacancyRepository->findDetailById(VacancyId::fromString($id));

        if ($vacancy === null) {
            throw new VacancyNotFoundException($id);
        }

        return $vacancy;
    }
}
