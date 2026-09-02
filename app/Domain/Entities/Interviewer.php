<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\OwnershipException\InterviewerVacancyEmployerMismatchException;
use App\Domain\Exceptions\StateConflictException\InterviewerAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\InterviewerIsNotActiveException;
use App\Domain\Exceptions\StateConflictException\NoActiveAssignmentException;
use App\Domain\Exceptions\ValidationException\InterviewerFullNameEmptyException;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\EntityIds\InterviewerVacancyAssignmentId;
use DateTimeImmutable;

final class Interviewer
{
    /** @var InterviewerVacancyAssignment[] */
    private array $vacancyAssignments = [];

    private function __construct(
        private readonly InterviewerId $id,
        private readonly EmployerId $employerId,
        private string $fullName,
        private ?string $position,
        private ?array $profileUrls,
        private bool $isActive,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private int $version,
        private ?DateTimeImmutable $deletedAt = null
    ) {
    }

    public static function create(
        InterviewerId $id,
        EmployerId $employerId,
        string $fullName,
        ?string $position = null,
        ?array $profileUrls = null,
        ?string $correlationId = null
    ): self {
        if (trim($fullName) === '') {
            throw new InterviewerFullNameEmptyException;
        }

        $now = new DateTimeImmutable;

        return new self(
            $id,
            $employerId,
            trim($fullName),
            $position,
            $profileUrls,
            true,
            $now,
            $now,
            1
        );
    }

    public function assignToVacancy(Vacancy $vacancy): void
    {
        if (!$this->isActive) {
            throw new InterviewerIsNotActiveException($this->id->value());
        }

        if (!$vacancy->employerId()->equals($this->employerId)) {
            throw new InterviewerVacancyEmployerMismatchException;
        }

        foreach ($this->vacancyAssignments as $assignment) {
            if ($assignment->vacancyId()->equals($vacancy->id()) && $assignment->isActive()) {
                throw new InterviewerAlreadyAssignedException($this->id->value());
            }
        }

        $assignment = new InterviewerVacancyAssignment(
            InterviewerVacancyAssignmentId::generate(),
            $this->id,
            $vacancy->id(),
            new DateTimeImmutable,
        );
        $this->vacancyAssignments[] = $assignment;
        $this->updatedAt = new DateTimeImmutable;
        $this->version++;
    }

    public function unassignFromVacancy(Vacancy $vacancy): void
    {
        foreach ($this->vacancyAssignments as $assignment) {
            if ($assignment->vacancyId()->equals($vacancy->id()) && $assignment->isActive()) {
                $assignment->deactivate();
                $this->updatedAt = new DateTimeImmutable;
                $this->version++;
                return;
            }
        }

        throw new NoActiveAssignmentException($vacancy->id()->value());
    }

    public function updateProfile(?string $fullName = null, ?string $position = null, ?array $profileUrls = null): void
    {
        if ($fullName !== null && trim($fullName) === '') {
            throw new InterviewerFullNameEmptyException;
        }
        $this->fullName = $fullName !== null ? trim($fullName) : $this->fullName;
        $this->position = $position ?? $this->position;
        $this->profileUrls = $profileUrls ?? $this->profileUrls;
        $this->updatedAt = new DateTimeImmutable;
        $this->version++;
    }

    public function softDelete(): void
    {
        $this->isActive = false;
        $this->deletedAt = new DateTimeImmutable;
        $this->version++;
    }

    public function id(): InterviewerId
    {
        return $this->id;
    }

    public function employerId(): EmployerId
    {
        return $this->employerId;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function position(): ?string
    {
        return $this->position;
    }

    public function profileUrls(): ?array
    {
        return $this->profileUrls;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    /** @return InterviewerVacancyAssignment[] */
    public function getVacancyAssignments(): array
    {
        return $this->vacancyAssignments;
    }
}