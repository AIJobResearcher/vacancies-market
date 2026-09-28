<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

final readonly class InterviewerSummaryDto
{
    /**
     * @param list<array{type: string, value: string}>|null $contacts
     */
    public function __construct(
        public string $id,
        public string $fullName,
        public ?string $position,
        public ?array $contacts,
        public ?string $avatarUrl,
    ) {
    }
}
