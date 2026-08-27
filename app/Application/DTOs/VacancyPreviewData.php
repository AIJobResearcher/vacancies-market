<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class VacancyPreviewData
{
    public function __construct(
        public string $id,
        public string $title,
        public string $employerName,
        public ?int $salaryMin,
        public ?int $salaryMax,
        public ?string $currency,
        public ?string $country,
        public ?string $city,
        public string $publishedAt
    ) {
    }
}
