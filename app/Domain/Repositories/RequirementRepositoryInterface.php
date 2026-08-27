<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Requirement;

interface RequirementRepositoryInterface
{
    public function findById(string $id): ?Requirement;
    public function findByTitleCaseInsensitive(string $title): ?Requirement;
    public function save(Requirement $requirement): void;
    public function isReferencedByActiveVacancyOrJob(string $requirementId): bool;
}