<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Location;

/** @psalm-suppress UnusedClass */
interface LocationRepositoryInterface
{
    /**
     * @return list<Location>
     */
    public function findAll(): array;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function findById(int $id): ?Location;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function save(Location $location): void;
}
