<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

final readonly class VacancyPreviewPageDto
{
    /**
     * @param array<int, VacancyPreviewDto> $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}
