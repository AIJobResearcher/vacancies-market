<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\StateConflictException\AssignmentAlreadyInactiveException;
use App\Domain\Exceptions\ValidationException\RelevanceScoreOutOfRangeException;
use DateTimeImmutable;

final class VacancyJobAssignment
{
    private ?DateTimeImmutable $unassignedAt = null;

    public function __construct(
        private string $id,
        private string $vacancyId,
        private string $jobId,
        private DateTimeImmutable $assignedAt,
        private ?int $relevanceScore = null,
        private int $version = 1,
    ) {
        if ($this->relevanceScore !== null && ($this->relevanceScore < 1 || $this->relevanceScore > 100)) {
            throw new RelevanceScoreOutOfRangeException($this->relevanceScore);
        }
    }

    /**
     * Marks the assignment as inactive by setting the unassigned timestamp.
     * Once unassigned, the assignment cannot be reactivated.
     */
    public function deactivate(): void
    {
        if ($this->unassignedAt !== null) {
            throw new AssignmentAlreadyInactiveException($this->unassignedAt->format(DATE_ATOM));
        }
        $this->unassignedAt = new DateTimeImmutable();
        $this->version++;
    }

    /**
     * Checks whether the assignment is currently active (no unassigned timestamp).
     */
    public function isActive(): bool
    {
        return $this->unassignedAt === null;
    }

    // Getters
    public function id(): string
    {
        return $this->id;
    }

    public function vacancyId(): string
    {
        return $this->vacancyId;
    }

    public function jobId(): string
    {
        return $this->jobId;
    }

    public function assignedAt(): DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function relevanceScore(): ?int
    {
        return $this->relevanceScore;
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