<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

use DateTimeImmutable;

final readonly class VacancyPreviewDto
{
    public function __construct(
        public string $id,
        public string $title,
        public string $employerId,
        public string $employerTitle,
        public int $minSalary,
        public ?int $maxSalary,
        public ?string $country,
        public ?string $city,
        public string $employmentType,
        public string $workplace,
        public string $status,
        public DateTimeImmutable $postedAt,
    ) {
    }
}
