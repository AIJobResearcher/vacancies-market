<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

interface MapperInterface
{
    public function toDomain(object $model): object;
    public function toEloquent(object $entity): object;
}