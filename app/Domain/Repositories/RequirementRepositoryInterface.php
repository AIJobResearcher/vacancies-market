<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Requirement;
use App\Domain\ValueObjects\EntityIds\RequirementId;

interface RequirementRepositoryInterface
{
    public function findById(RequirementId $id): ?Requirement;
    public function findByTitleCaseInsensitive(string $title): ?Requirement;
    public function save(Requirement $requirement): void;
    public function isReferencedByActiveVacancyOrJob(RequirementId $requirementId): bool;
}
