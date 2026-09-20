<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use DateTimeImmutable;

final readonly class VacancySearchCriteria
{
    public function __construct(
        public JobId $jobId,
        public ?EmployerId $employerId = null,
        public ?string $country = null,
        public ?string $city = null,
        public ?int $minSalary = null,
        public ?int $maxSalary = null,
        public ?VacancyStatusEnum $status = null,
        public ?WorkplaceEnum $workplace = null,
        public ?EmploymentTypeEnum $employmentType = null,
        public ?DateTimeImmutable $postedFrom = null,
        public ?DateTimeImmutable $postedTo = null,
    ) {
    }
}
