<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use DateTimeImmutable;

final class VacancyRequirementAssignment
{
    public function __construct(
        private string $id,
        private string $vacancyId,
        private string $requirementId,
        private DateTimeImmutable $assignedAt,
        private int $version = 1
    ) {}

    public function getRequirementId(): string
    {
        return $this->requirementId;
    }
}