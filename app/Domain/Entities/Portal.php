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
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function createPortal(
        PortalId $id,
        string $code,
        string $name,
        ?string $baseUrl = null,
    ): self {
        self::assertCode($code);
        self::assertName($name);
        self::assertBaseUrl($baseUrl);

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
    public function updatePortal(
        ?string $code = null,
        ?string $name = null,
        ?string $baseUrl = null,
    ): void {
        if ($code !== null) {
            self::assertCode($code);
        }

        if ($name !== null) {
            self::assertName($name);
        }

        if ($baseUrl !== null) {
            self::assertBaseUrl($baseUrl);
        }

        $this->code = $code ?? $this->code;
        $this->name = $name ?? $this->name;
        $this->baseUrl = $baseUrl ?? $this->baseUrl;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Deletes the Portal (5.6.3): the row is removed by the repository, so the
     * entity carries no state to change.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function deletePortal(): void
    {
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

    private static function assertCode(string $code): void
    {
        if ($code === '') {
            throw new PortalCodeEmptyException();
        }
    }

    private static function assertName(string $name): void
    {
        if ($name === '') {
            throw new PortalNameEmptyException();
        }
    }

    private static function assertBaseUrl(?string $baseUrl): void
    {
        if ($baseUrl === '') {
            throw new PortalBaseUrlEmptyException();
        }
    }
}
