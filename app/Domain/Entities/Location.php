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
        private readonly string $name,
        private readonly ?string $isoName,
        private readonly ?int $parentId,
        private readonly LocationTypeEnum $type,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
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
