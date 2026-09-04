<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\VacancyRequirementAssignment;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\EntityIds\VacancyRequirementAssignmentId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VacancyRequirementAssignmentTest extends TestCase
{
    public function testConstructAndGetRequirementId(): void
    {
        $requirementId = RequirementId::generate();

        $assignment = new VacancyRequirementAssignment(
            VacancyRequirementAssignmentId::generate(),
            VacancyId::generate(),
            $requirementId,
            new DateTimeImmutable()
        );

        $this->assertEquals($requirementId, $assignment->getRequirementId());
    }
}
