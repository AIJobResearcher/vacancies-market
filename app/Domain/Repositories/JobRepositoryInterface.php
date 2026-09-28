<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\DTOs\JobPreviewPageDto;

/** @psalm-suppress UnusedClass */
interface JobRepositoryInterface
{
    /**
     * @param list<string> $ids
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function findPreviewsByIds(array $ids): JobPreviewPageDto;
}
