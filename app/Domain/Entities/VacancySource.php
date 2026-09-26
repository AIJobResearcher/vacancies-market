<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\EntityIds\VacancySourceId;
use DateTimeImmutable;

final class VacancySource
{
    public function __construct(
        private readonly VacancySourceId $id,
        private readonly VacancyId $vacancyId,
        private string $sourceKey,
        private readonly string $externalVacancyId,
        private string $externalUrl,
        private DateTimeImmutable $firstSeenAt,
        private DateTimeImmutable $lastSeenAt,
        private ?DateTimeImmutable $closedAt = null,
        private bool $isPrimary = false
    ) {
    }

    public function updateLastSeenAt(DateTimeImmutable $time): void
    {
        $this->lastSeenAt = $time;
    }

    public function sourceKey(): string
    {
        return $this->sourceKey;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function id(): VacancySourceId
    {
        return $this->id;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function vacancyId(): VacancyId
    {
        return $this->vacancyId;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function firstSeenAt(): DateTimeImmutable
    {
        return $this->firstSeenAt;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function closedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function externalVacancyId(): string
    {
        return $this->externalVacancyId;
    }

    public function externalUrl(): string
    {
        return $this->externalUrl;
    }

    public function lastSeenAt(): DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }
}
