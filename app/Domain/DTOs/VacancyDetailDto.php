<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

use DateTimeImmutable;

final readonly class VacancyDetailDto
{
    public function __construct(
        public string $id,
        public string $title,
        public EmployerSummaryDto $employer,
        public int $minSalary,
        public ?int $maxSalary,
        public ?string $country,
        public ?string $city,
        public string $employmentType,
        public string $workplace,
        public string $status,
        public DateTimeImmutable $postedAt,
        public ?string $description,
        /** @var list<string> */
        public array $requirements,
        public ?string $internalUrl,
        /** @var list<string> */
        public array $externalUrls,
        public ?InterviewerSummaryDto $interviewer,
        public ?DateTimeImmutable $closedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public int $version,
    ) {
    }
}
