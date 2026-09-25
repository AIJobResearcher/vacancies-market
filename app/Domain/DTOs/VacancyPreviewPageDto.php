<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

final readonly class VacancyPreviewPageDto
{
    /**
     * @param list<VacancyPreviewDto> $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}
