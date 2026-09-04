<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

interface MapperInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function toDomain(object $model): object;
    /** @psalm-suppress PossiblyUnusedMethod */
    public function toEloquent(object $entity): object;
}
