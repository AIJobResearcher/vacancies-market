<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\InvalidOperationException;
use DateTimeImmutable;

final class Interviewer
{
    private function __construct(
        private string $id,
        private string $employerId,
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
        string $id,
        string $employerId,
        string $fullName,
        ?string $position = null,
        ?array $profileUrls = null,
        ?string $correlationId = null
    ): self {
        if (trim($fullName) === '') {
            throw new InvalidOperationException('Interviewer full name cannot be empty.');
        }
        $now = new DateTimeImmutable();
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
        if ($vacancy->employerId() !== $this->employerId) {
            throw new InvalidOperationException('Interviewer and vacancy belong to different employers.');
        }
        // Assignment is handled via InterviewerVacancyAssignment entity; no state change here.
    }

    public function unassignFromVacancy(Vacancy $vacancy): void
    {
        // No state change.
    }

    public function updateProfile(?string $fullName = null, ?string $position = null, ?array $profileUrls = null): void
    {
        if ($fullName !== null && trim($fullName) === '') {
            throw new InvalidOperationException('Full name cannot be empty.');
        }
        $this->fullName = $fullName !== null ? trim($fullName) : $this->fullName;
        $this->position = $position ?? $this->position;
        $this->profileUrls = $profileUrls ?? $this->profileUrls;
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function softDelete(): void
    {
        $this->isActive = false;
        $this->deletedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function employerId(): string
    {
        return $this->employerId;
    }

    public function version(): int
    {
        return $this->version;
    }
}