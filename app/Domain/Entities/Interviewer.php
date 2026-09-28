<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\ValidationException\InterviewerFullNameEmptyException;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\InterviewerContacts;
use DateTimeImmutable;

final class Interviewer
{
    private function __construct(
        private readonly InterviewerId $id,
        private string $fullName,
        private ?string $position,
        private ?InterviewerContacts $contacts,
        private ?string $avatarUrl,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private int $version,
        private ?DateTimeImmutable $deletedAt = null
    ) {
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     * @psalm-suppress UnusedParam
     */
    public static function create(
        InterviewerId $id,
        string $fullName,
        ?string $position = null,
        ?InterviewerContacts $contacts = null,
        ?string $avatarUrl = null,
        ?string $correlationId = null
    ): self {
        if (trim($fullName) === '') {
            throw new InterviewerFullNameEmptyException();
        }

        $now = new DateTimeImmutable();

        return new self(
            $id,
            trim($fullName),
            $position,
            $contacts,
            $avatarUrl,
            $now,
            $now,
            1
        );
    }

    /**
     * Restores an Interviewer from persisted state without validation or events.
     */
    public static function reconstitute(
        InterviewerId $id,
        string $fullName,
        ?string $position,
        ?InterviewerContacts $contacts,
        ?string $avatarUrl,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
        ?DateTimeImmutable $deletedAt = null,
    ): self {
        return new self(
            $id,
            $fullName,
            $position,
            $contacts,
            $avatarUrl,
            $createdAt,
            $updatedAt,
            $version,
            $deletedAt,
        );
    }

    public function updateProfile(
        ?string $fullName = null,
        ?string $position = null,
        ?InterviewerContacts $contacts = null,
        ?string $avatarUrl = null
    ): void {
        if ($fullName !== null && trim($fullName) === '') {
            throw new InterviewerFullNameEmptyException();
        }
        $this->fullName = $fullName !== null ? trim($fullName) : $this->fullName;
        $this->position = $position ?? $this->position;
        $this->contacts = $contacts ?? $this->contacts;
        $this->avatarUrl = $avatarUrl ?? $this->avatarUrl;
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function id(): InterviewerId
    {
        return $this->id;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function position(): ?string
    {
        return $this->position;
    }

    public function contacts(): ?InterviewerContacts
    {
        return $this->contacts;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
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
}
