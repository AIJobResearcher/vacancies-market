<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use DateTimeImmutable;

final readonly class GetVacanciesByJobIdFilterDto
{
    /**
     * @param list<EmployerId> $employerIds
     * @param list<int> $locationIds
     * @param list<WorkplaceEnum> $workplaces
     * @param list<EmploymentTypeEnum> $employmentTypes
     */
    public function __construct(
        public JobId $jobId,
        public array $employerIds = [],
        public array $locationIds = [],
        public ?int $minSalary = null,
        public ?int $maxSalary = null,
        public ?VacancyStatusEnum $status = null,
        public array $workplaces = [],
        public array $employmentTypes = [],
        public ?DateTimeImmutable $postedFrom = null,
        public ?DateTimeImmutable $postedTo = null,
        public ?int $page = null,
        public ?int $perPage = null,
    ) {
    }
}
