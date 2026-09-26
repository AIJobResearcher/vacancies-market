<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

use App\Domain\Entities\Requirement;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use InvalidArgumentException;
use Override;

final class RequirementMapper extends AbstractMapper
{
    #[Override]
    public function toDomain(object $model): Requirement
    {
        if (! $model instanceof RequirementModel) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', RequirementModel::class, $model::class));
        }

        return Requirement::reconstitute(
            id: RequirementId::fromString($model->id),
            title: $model->title,
            description: $model->description,
            category: $model->category,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    #[Override]
    public function toEloquent(object $entity): RequirementModel
    {
        if (! $entity instanceof Requirement) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', Requirement::class, $entity::class));
        }

        $model = new RequirementModel();
        $model->id = $entity->id()->value();
        $model->title = $entity->title();
        $model->description = $entity->description();
        $model->category = $entity->category();

        return $model;
    }

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     description: string|null,
     *     category: string|null
     * }
     */
    public function toPersistenceState(Requirement $entity): array
    {
        return [
            'id' => $entity->id()->value(),
            'title' => $entity->title(),
            'description' => $entity->description(),
            'category' => $entity->category(),
        ];
    }
}
