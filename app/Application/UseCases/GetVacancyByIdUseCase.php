<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Exceptions\EntityNotFoundException\VacancyNotFoundException;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\VacancyId;

final class GetVacancyByIdUseCase
{
    public function __construct(private readonly VacancyRepositoryInterface $vacancyRepository)
    {
    }

    /**
     * @return array<string, mixed>
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
