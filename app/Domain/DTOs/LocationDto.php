<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

use App\Domain\Enums\LocationTypeEnum;
use DateTimeImmutable;

final readonly class LocationDto
{
    /**
     * @param list<LocationDto> $children
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $isoName,
        public ?int $parentId,
        public LocationTypeEnum $type,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $children,
    ) {
    }
}
