<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Repositories\PortalRepositoryInterface;
use App\Infrastructure\Eloquents\Mappers\PortalMapper;

final class PortalEloquentRepository implements PortalRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedProperty */
    public function __construct(private readonly PortalMapper $mapper)
    {
    }
}
