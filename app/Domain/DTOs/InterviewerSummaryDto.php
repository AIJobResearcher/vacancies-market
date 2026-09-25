<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

final readonly class InterviewerSummaryDto
{
    public function __construct(
        public string $id,
        public string $fullName,
        public ?string $position,
        /** @var array<string, string>|null */
        public ?array $profileUrls,
    ) {
    }
}
