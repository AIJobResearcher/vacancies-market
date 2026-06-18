++<?php
declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Vacancy;

interface VacancyRepositoryInterface
{
    public function save(Vacancy $vacancy): void;

    public function findById(string $id): ?Vacancy;

    /** @return Vacancy[] */
    public function findByEmployerId(string $employerId): array;

    public function remove(string $id): void;
}
