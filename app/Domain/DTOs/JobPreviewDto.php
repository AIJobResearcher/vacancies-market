<?php

declare(strict_types=1);

namespace App\Domain\DTOs;

final readonly class JobPreviewDto
{
    public function __construct(
        public string $id,
        public string $title,
        public string $category,
        public ?string $subCategory,
        public ?string $parentJobId,
        public ?string $parentJobTitle,
    ) {
    }
}
