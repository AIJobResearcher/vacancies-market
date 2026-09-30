<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

use DateTimeImmutable;

final readonly class VacancyDetailDto
{
    /**
     * @param list<RequirementSummaryDto> $requirements
     * @param list<InterviewerSummaryDto> $interviewers
     * @param list<SourceDto> $sources
     */
    public function __construct(
        public VacancyPreviewDto $preview,
        public array $requirements,
        public array $interviewers,
        public array $sources,
        public ?DateTimeImmutable $closedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public int $version,
    ) {
    }
}
