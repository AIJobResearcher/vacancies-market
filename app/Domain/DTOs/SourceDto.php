<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

use DateTimeImmutable;

final readonly class SourceDto
{
    /**
     * @param list<ContentDto> $contents
     */
    public function __construct(
        public string $id,
        public string $externalUrl,
        public string $title,
        public DateTimeImmutable $postedAt,
        public array $contents,
    ) {
    }
}
