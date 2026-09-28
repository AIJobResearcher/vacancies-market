<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Repositories\LocationRepositoryInterface;
use App\Infrastructure\Eloquents\Mappers\LocationMapper;
use App\Infrastructure\Eloquents\Models\LocationModel;
use Override;

final class LocationEloquentRepository implements LocationRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private readonly LocationMapper $mapper)
    {
    }

    #[Override]
    public function findAll(): array
    {
        $locations = LocationModel::query()
            ->orderByRaw('parent_id asc nulls first')
            ->orderBy('name')
            ->get();

        $result = [];

        foreach ($locations as $location) {
            $result[] = $this->mapper->toDomain($location);
        }

        return $result;
    }
}
