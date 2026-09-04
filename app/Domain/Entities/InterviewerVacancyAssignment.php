<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\StateConflictException\AssignmentAlreadyInactiveException;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\EntityIds\InterviewerVacancyAssignmentId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use DateTimeImmutable;

final class InterviewerVacancyAssignment
{
    private ?DateTimeImmutable $unassignedAt = null;
    private int $version = 1;

    public function __construct(
        private readonly InterviewerVacancyAssignmentId $id,
        private readonly InterviewerId $interviewerId,
        private readonly VacancyId $vacancyId,
        private DateTimeImmutable $assignedAt,
    ) {
    }

    public function deactivate(): void
    {
        if ($this->unassignedAt !== null) {
            throw new AssignmentAlreadyInactiveException($this->unassignedAt->format(DATE_ATOM));
        }

        $this->unassignedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function isActive(): bool
    {
        return $this->unassignedAt === null;
    }

    public function id(): InterviewerVacancyAssignmentId
    {
        return $this->id;
    }

    public function interviewerId(): InterviewerId
    {
        return $this->interviewerId;
    }

    public function vacancyId(): VacancyId
    {
        return $this->vacancyId;
    }

    public function assignedAt(): DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function unassignedAt(): ?DateTimeImmutable
    {
        return $this->unassignedAt;
    }

    public function version(): int
    {
        return $this->version;
    }
}
