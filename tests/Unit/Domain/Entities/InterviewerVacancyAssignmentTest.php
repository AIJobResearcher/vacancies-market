<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\InterviewerVacancyAssignment;
use App\Domain\Exceptions\StateConflictException\AssignmentAlreadyInactiveException;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\EntityIds\InterviewerVacancyAssignmentId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InterviewerVacancyAssignmentTest extends TestCase
{
    public function testConstruct(): void
    {
        $assignmentId = InterviewerVacancyAssignmentId::generate();
        $interviewerId = InterviewerId::generate();
        $vacancyId = VacancyId::generate();
        $assignedAt = new DateTimeImmutable();

        $assignment = new InterviewerVacancyAssignment(
            $assignmentId,
            $interviewerId,
            $vacancyId,
            $assignedAt
        );

        $this->assertTrue($assignment->isActive());
        $this->assertEquals($assignmentId, $assignment->id());
        $this->assertEquals($interviewerId, $assignment->interviewerId());
        $this->assertEquals($vacancyId, $assignment->vacancyId());
        $this->assertEquals($assignedAt, $assignment->assignedAt());
        $this->assertNull($assignment->unassignedAt());
        $this->assertEquals(1, $assignment->version());
    }

    public function testDeactivate(): void
    {
        $assignment = new InterviewerVacancyAssignment(
            InterviewerVacancyAssignmentId::generate(),
            InterviewerId::generate(),
            VacancyId::generate(),
            new DateTimeImmutable()
        );
        $this->assertTrue($assignment->isActive());

        $assignment->deactivate();
        $this->assertFalse($assignment->isActive());
        $this->assertNotNull($assignment->unassignedAt());
        $this->assertEquals(2, $assignment->version());
    }

    public function testDeactivateAlreadyInactiveThrows(): void
    {
        $assignment = new InterviewerVacancyAssignment(
            InterviewerVacancyAssignmentId::generate(),
            InterviewerId::generate(),
            VacancyId::generate(),
            new DateTimeImmutable()
        );
        $assignment->deactivate();
        $this->expectException(AssignmentAlreadyInactiveException::class);
        $assignment->deactivate();
    }
}
