<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Exceptions\InvalidOperationException;
use App\Domain\Repositories\RequirementRepositoryInterface;

final readonly class RequirementUniquenessCheckerService
{
    public function __construct(private RequirementRepositoryInterface $repository) {}

    public function ensureUnique(string $title, ?string $excludeId = null): void
    {
        $existing = $this->repository->findByTitleCaseInsensitive($title);
        if ($existing !== null && ($excludeId === null || $existing->id() !== $excludeId)) {
            throw new InvalidOperationException(sprintf('Requirement with title "%s" already exists.', $title));
        }
    }
}