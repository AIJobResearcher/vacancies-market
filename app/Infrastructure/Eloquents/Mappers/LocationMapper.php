<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

use App\Domain\Entities\Location;
use App\Domain\Enums\LocationTypeEnum;
use App\Infrastructure\Eloquents\Models\LocationModel;
use InvalidArgumentException;
use Override;

final class LocationMapper extends AbstractMapper
{
    #[Override]
    public function toDomain(object $model): Location
    {
        if (! $model instanceof LocationModel) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', LocationModel::class, $model::class));
        }

        return Location::reconstitute(
            id: $model->id,
            name: $model->name,
            isoName: $model->iso_name,
            parentId: $model->parent_id,
            type: LocationTypeEnum::from($model->type),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    #[Override]
    public function toEloquent(object $entity): LocationModel
    {
        if (! $entity instanceof Location) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', Location::class, $entity::class));
        }

        $model = new LocationModel();
        $model->id = $entity->id();
        $model->name = $entity->name();
        $model->iso_name = $entity->isoName();
        $model->parent_id = $entity->parentId();
        $model->type = $entity->type()->value;

        return $model;
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     iso_name: string|null,
     *     parent_id: int|null,
     *     type: string
     * }
     */
    public function toPersistenceState(Location $entity): array
    {
        return [
            'id' => $entity->id(),
            'name' => $entity->name(),
            'iso_name' => $entity->isoName(),
            'parent_id' => $entity->parentId(),
            'type' => $entity->type()->value,
        ];
    }
}
