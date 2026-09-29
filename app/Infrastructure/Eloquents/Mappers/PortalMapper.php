<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

use App\Domain\Entities\Portal;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Infrastructure\Eloquents\Models\PortalModel;
use InvalidArgumentException;
use Override;

final class PortalMapper extends AbstractMapper
{
    #[Override]
    public function toDomain(object $model): Portal
    {
        if (! $model instanceof PortalModel) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', PortalModel::class, $model::class));
        }

        return Portal::reconstitute(
            id: PortalId::fromString($model->id),
            code: $model->code,
            name: $model->name,
            baseUrl: $model->base_url,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    #[Override]
    public function toEloquent(object $entity): PortalModel
    {
        if (! $entity instanceof Portal) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', Portal::class, $entity::class));
        }

        $model = new PortalModel();
        $model->id = $entity->id()->value();
        $model->code = $entity->code();
        $model->name = $entity->name();
        $model->base_url = $entity->baseUrl();

        return $model;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @return array{
     *     id: string,
     *     code: string,
     *     name: string,
     *     base_url: string|null
     * }
     */
    public function toPersistenceState(Portal $entity): array
    {
        return [
            'id' => $entity->id()->value(),
            'code' => $entity->code(),
            'name' => $entity->name(),
            'base_url' => $entity->baseUrl(),
        ];
    }
}
