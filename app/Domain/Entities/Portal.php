<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\ValidationException\PortalBaseUrlEmptyException;
use App\Domain\Exceptions\ValidationException\PortalNameEmptyException;
use App\Domain\ValueObjects\EntityIds\PortalId;
use DateTimeImmutable;

final class Portal
{
    public function __construct(
        private readonly PortalId $id,
        private string $name,
        private string $baseUrl,
        private ?string $apiEndpoint = null,
        private int $crawlDelaySeconds = 0,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
    ) {
        if ($this->name === '') {
            throw new PortalNameEmptyException();
        }

        if ($this->baseUrl === '') {
            throw new PortalBaseUrlEmptyException();
        }

        $this->createdAt ??= new DateTimeImmutable();
        $this->updatedAt ??= $this->createdAt;
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

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateConfig(
        ?string $name = null,
        ?string $baseUrl = null,
        ?string $apiEndpoint = null,
        ?int $crawlDelaySeconds = null
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
    }
}
