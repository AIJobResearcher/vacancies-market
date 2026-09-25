<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

final readonly class EmployerSummaryDto
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public ?string $website,
        public ?string $email,
        public ?string $phone,
        public ?string $logoUrl,
    ) {
    }
}
