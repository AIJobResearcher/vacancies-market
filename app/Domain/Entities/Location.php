<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Enums\LocationTypeEnum;
use App\Domain\Exceptions\ValidationException\LocationNameEmptyException;
use DateTimeImmutable;

/**
 * @psalm-suppress UnusedClass
 * @psalm-suppress PossiblyUnusedMethod
 */
final class Location
{
    private function __construct(
        private readonly int $id,
        private string $name,
        private ?string $isoName,
        private ?int $parentId,
        private LocationTypeEnum $type,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function createLocation(
        int $id,
        string $name,
        LocationTypeEnum $type,
        ?string $isoName = null,
        ?int $parentId = null,
    ): self {
        if (trim($name) === '') {
            throw new LocationNameEmptyException();
        }

        $now = new DateTimeImmutable();

        return new self($id, trim($name), $isoName, $parentId, $type, $now, $now);
    }

    /**
     * Restores a Location from persisted state without validation.
     */
    public static function reconstitute(
        int $id,
        string $name,
        ?string $isoName,
        ?int $parentId,
        LocationTypeEnum $type,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $isoName, $parentId, $type, $createdAt, $updatedAt);
    }

    public function updateLocation(
        ?string $name = null,
        ?string $isoName = null,
        ?int $parentId = null,
        ?LocationTypeEnum $type = null
    ): void {
        if ($name !== null && trim($name) === '') {
            throw new LocationNameEmptyException();
        }

        $this->name = $name !== null ? trim($name) : $this->name;
        $this->isoName = $isoName ?? $this->isoName;
        $this->parentId = $parentId ?? $this->parentId;
        $this->type = $type ?? $this->type;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Deletes the Location dictionary entry (5.8.3): the row is removed by the
     * repository, so the entity carries no state to change.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function deleteLocation(): void
    {
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function isoName(): ?string
    {
        return $this->isoName;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function parentId(): ?int
    {
        return $this->parentId;
    }

    public function type(): LocationTypeEnum
    {
        return $this->type;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
