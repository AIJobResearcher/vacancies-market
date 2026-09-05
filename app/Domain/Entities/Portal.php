<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\ValidationException\PortalBaseUrlEmptyException;
use App\Domain\Exceptions\ValidationException\PortalNameEmptyException;
use App\Domain\ValueObjects\EntityIds\PortalId;
use DateTimeImmutable;

final class Portal
{
    private function __construct(
        private readonly PortalId $id,
        private string $name,
        private string $baseUrl,
        private ?string $apiEndpoint,
        private int $crawlDelaySeconds,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private int $version,
    ) {
        if ($name === '') {
            throw new PortalNameEmptyException();
        }

        if ($baseUrl === '') {
            throw new PortalBaseUrlEmptyException();
        }
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function create(
        PortalId $id,
        string $name,
        string $baseUrl,
        ?string $apiEndpoint = null,
        int $crawlDelaySeconds = 0,
    ): self {
        $now = new DateTimeImmutable();

        return new self($id, $name, $baseUrl, $apiEndpoint, $crawlDelaySeconds, $now, $now, 1);
    }

    /**
     * Restores a Portal from persisted state without validation or events.
     */
    public static function reconstitute(
        PortalId $id,
        string $name,
        string $baseUrl,
        ?string $apiEndpoint,
        int $crawlDelaySeconds,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        return new self($id, $name, $baseUrl, $apiEndpoint, $crawlDelaySeconds, $createdAt, $updatedAt, $version);
    }

    public function id(): PortalId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function apiEndpoint(): ?string
    {
        return $this->apiEndpoint;
    }

    public function crawlDelaySeconds(): int
    {
        return $this->crawlDelaySeconds;
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

    /** @psalm-suppress PossiblyUnusedMethod */
    public function version(): int
    {
        return $this->version;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function updateConfig(
        ?string $name = null,
        ?string $baseUrl = null,
        ?string $apiEndpoint = null,
        ?int $crawlDelaySeconds = null,
    ): void {
        if ($name !== null && $name === '') {
            throw new PortalNameEmptyException();
        }

        if ($baseUrl !== null && $baseUrl === '') {
            throw new PortalBaseUrlEmptyException();
        }

        $this->name = $name ?? $this->name;
        $this->baseUrl = $baseUrl ?? $this->baseUrl;
        $this->apiEndpoint = $apiEndpoint ?? $this->apiEndpoint;
        $this->crawlDelaySeconds = $crawlDelaySeconds ?? $this->crawlDelaySeconds;
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }
}
