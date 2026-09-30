<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

final readonly class JobPreviewPageDto
{
    /**
     * @param array<int, JobPreviewDto> $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}
