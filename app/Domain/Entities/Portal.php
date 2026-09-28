<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\ValidationException\PortalBaseUrlEmptyException;
use App\Domain\Exceptions\ValidationException\PortalCodeEmptyException;
use App\Domain\Exceptions\ValidationException\PortalNameEmptyException;
use App\Domain\ValueObjects\EntityIds\PortalId;
use DateTimeImmutable;

final class Portal
{
    private function __construct(
        private readonly PortalId $id,
        private string $code,
        private string $name,
        private ?string $baseUrl,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        if ($code === '') {
            throw new PortalCodeEmptyException();
        }

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
        string $code,
        string $name,
        ?string $baseUrl = null,
    ): self {
        $now = new DateTimeImmutable();

        return new self($id, $code, $name, $baseUrl, $now, $now);
    }

    /**
     * Restores a Portal from persisted state without validation or events.
     */
    public static function reconstitute(
        PortalId $id,
        string $code,
        string $name,
        ?string $baseUrl,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $code, $name, $baseUrl, $createdAt, $updatedAt);
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function update(
        ?string $code = null,
        ?string $name = null,
        ?string $baseUrl = null,
    ): void {
        if ($code !== null && $code === '') {
            throw new PortalCodeEmptyException();
        }

        if ($name !== null && $name === '') {
            throw new PortalNameEmptyException();
        }

        if ($baseUrl !== null && $baseUrl === '') {
            throw new PortalBaseUrlEmptyException();
        }

        $this->code = $code ?? $this->code;
        $this->name = $name ?? $this->name;
        $this->baseUrl = $baseUrl ?? $this->baseUrl;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function id(): PortalId
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function baseUrl(): ?string
    {
        return $this->baseUrl;
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
}
