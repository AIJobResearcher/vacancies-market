<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use DateTimeImmutable;

final class VacancySource
{
    public function __construct(
        private string $id,
        private string $vacancyId,
        private string $sourceKey,
        private string $externalVacancyId,
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