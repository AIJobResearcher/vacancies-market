<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Exceptions\ValidationException\RequirementAlreadyExistsException;
use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\RequirementId;

final readonly class RequirementUniquenessCheckerService
{
    public function __construct(private RequirementRepositoryInterface $repository) {}

    public function ensureUnique(string $title, ?RequirementId $excludeId = null): void
    {
        $existing = $this->repository->findByTitleCaseInsensitive($title);
        if ($existing !== null && ($excludeId === null || !$existing->id()->equals($excludeId))) {
            throw new RequirementAlreadyExistsException($title);
        }
    }
}