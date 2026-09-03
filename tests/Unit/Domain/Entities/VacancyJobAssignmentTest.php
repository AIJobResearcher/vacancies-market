<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\VacancyJobAssignment;
use App\Domain\Exceptions\StateConflictException\AssignmentAlreadyInactiveException;
use App\Domain\Exceptions\ValidationException\RelevanceScoreOutOfRangeException;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\EntityIds\VacancyJobAssignmentId;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VacancyJobAssignmentTest extends TestCase
{
    public static function relevanceScoreProvider(): array
    {
        return [
            'null' => [null],
            'valid low' => [1],
            'valid high' => [100],
            'valid middle' => [50],
        ];
    }

    public static function invalidRelevanceProvider(): array
    {
        return [
            'too low' => [0],
            'too high' => [101],
            'negative' => [-10],
        ];
    }

    #[DataProvider('relevanceScoreProvider')]
    public function test_construct_valid(?int $score): void
    {
        $assignmentId = VacancyJobAssignmentId::generate();
        $vacancyId = VacancyId::generate();
        $jobId = JobId::generate();
        $assignedAt = new DateTimeImmutable;
        $assignment = new VacancyJobAssignment($assignmentId, $vacancyId, $jobId, $assignedAt, $score);
        $this->assertEquals($score, $assignment->relevanceScore());
        $this->assertTrue($assignment->isActive());
        $this->assertEquals(1, $assignment->version());
        $this->assertEquals($assignmentId, $assignment->id());
        $this->assertEquals($vacancyId, $assignment->vacancyId());
        $this->assertEquals($jobId, $assignment->jobId());
        $this->assertEquals($assignedAt, $assignment->assignedAt());
    }

    #[DataProvider('invalidRelevanceProvider')]
    public function test_construct_invalid_relevance_throws(int $score): void
    {
        $this->expectException(RelevanceScoreOutOfRangeException::class);
        new VacancyJobAssignment(
            VacancyJobAssignmentId::generate(),
            VacancyId::generate(),
            JobId::generate(),
            new DateTimeImmutable,
            $score
        );
    }

    public function test_deactivate(): void
    {
        $assignment = new VacancyJobAssignment(
            VacancyJobAssignmentId::generate(),
            VacancyId::generate(),
            JobId::generate(),
            new DateTimeImmutable
        );
        $assignment->deactivate();
        $this->assertFalse($assignment->isActive());
        $this->assertNotNull($assignment->unassignedAt());
        $this->assertEquals(2, $assignment->version());
    }

    public function test_deactivate_already_inactive_throws(): void
    {
        $assignment = new VacancyJobAssignment(
            VacancyJobAssignmentId::generate(),
            VacancyId::generate(),
            JobId::generate(),
            new DateTimeImmutable
        );
        $assignment->deactivate();
        $this->expectException(AssignmentAlreadyInactiveException::class);
        $assignment->deactivate();
    }
}
