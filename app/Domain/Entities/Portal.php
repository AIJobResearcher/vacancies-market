<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\ValidationException\PortalBaseUrlEmptyException;
use App\Domain\Exceptions\ValidationException\PortalNameEmptyException;
use App\Domain\ValueObjects\EntityIds\PortalId;
use DateTimeImmutable;

final class Portal
{
    private PortalId $id;
    private string $name;
    private string $baseUrl;
    private ?string $apiEndpoint;
    private int $crawlDelaySeconds;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        PortalId $id,
        string $name,
        string $baseUrl,
        ?string $apiEndpoint = null,
        int $crawlDelaySeconds = 0,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        if ($name === '') {
            throw new PortalNameEmptyException();
        }

        if ($baseUrl === '') {
            throw new PortalBaseUrlEmptyException();
        }

        $this->id = $id;
        $this->name = $name;
        $this->baseUrl = $baseUrl;
        $this->apiEndpoint = $apiEndpoint;
        $this->crawlDelaySeconds = $crawlDelaySeconds;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? $this->createdAt;
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
