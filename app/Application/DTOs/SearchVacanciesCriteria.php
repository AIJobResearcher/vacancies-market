<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Application\Enums\VacancySort;
use App\Domain\Enums\VacancyStatusEnum;
use InvalidArgumentException;

final readonly class SearchVacanciesCriteria
{
    public function __construct(
        public ?string $query,
        public ?string $employerId,
        public ?string $country,
        public ?string $city,
        public ?int $salaryMin,
        public ?int $salaryMax,
        public ?VacancyStatusEnum $status,
        public int $perPage = 20,
        public int $page = 1,
        public VacancySort $sort = VacancySort::Relevance
    ) {
        if ($this->perPage < 1 || $this->perPage > 100) {
            throw new InvalidArgumentException('perPage must be between 1 and 100.');
        }

        if ($this->page < 1) {
            throw new InvalidArgumentException('page must be at least 1.');
        }

        if ($this->salaryMin !== null && $this->salaryMax !== null && $this->salaryMin > $this->salaryMax) {
            throw new InvalidArgumentException('salaryMin must be less than or equal to salaryMax.');
        }
    }
}
