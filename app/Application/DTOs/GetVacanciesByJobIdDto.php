<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use DateTimeImmutable;

final readonly class GetVacanciesByJobIdDto
{
    public function __construct(
        public string $jobId,
        public ?string $employerId,
        public ?string $country,
        public ?string $city,
        public ?int $minSalary,
        public ?int $maxSalary,
        public ?VacancyStatusEnum $status,
        public ?WorkplaceEnum $workplace,
        public ?EmploymentTypeEnum $employmentType,
        public ?DateTimeImmutable $postedFrom,
        public ?DateTimeImmutable $postedTo,
        public ?int $perPage,
        public ?int $page,
    ) {
    }
}
