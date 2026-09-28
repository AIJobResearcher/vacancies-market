<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Infrastructure\Eloquents\Mappers\RequirementMapper;

final class RequirementEloquentRepository implements RequirementRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedProperty */
    public function __construct(private readonly RequirementMapper $mapper)
    {
    }
}
