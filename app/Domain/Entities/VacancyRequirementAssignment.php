<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\EntityIds\VacancyRequirementAssignmentId;
use DateTimeImmutable;

final class VacancyRequirementAssignment
{
    public function __construct(
        private readonly VacancyRequirementAssignmentId $id,
        private readonly VacancyId $vacancyId,
        private readonly RequirementId $requirementId,
        private DateTimeImmutable $assignedAt,
        private int $version = 1
    ) {
    }

    public function getRequirementId(): RequirementId
    {
        return $this->requirementId;
    }
}
