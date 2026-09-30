<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

final readonly class VacancyPreviewDto
{
    /**
     * @param list<int> $researcherLocationIds
     * @param list<string> $employmentTypes
     * @param list<string> $workplaces
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $employerId,
        public string $employerTitle,
        public int $minSalary,
        public ?int $maxSalary,
        public array $researcherLocationIds,
        public array $employmentTypes,
        public array $workplaces,
        public string $status,
    ) {
    }
}
