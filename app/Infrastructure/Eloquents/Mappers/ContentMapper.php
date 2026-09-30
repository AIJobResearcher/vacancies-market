<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

use App\Domain\DTOs\ContentDto;
use App\Domain\Entities\Content;
use App\Domain\Enums\ContentTypeEnum;
use App\Domain\ValueObjects\EntityIds\ContentId;
use App\Domain\ValueObjects\EntityIds\SourceId;
use App\Infrastructure\Eloquents\Models\ContentModel;
use InvalidArgumentException;
use Override;

final class ContentMapper extends AbstractMapper
{
    #[Override]
    public function toDomain(object $model): Content
    {
        if (! $model instanceof ContentModel) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', ContentModel::class, $model::class));
        }

        return Content::reconstitute(
            ContentId::fromString($model->id),
            SourceId::fromString($model->source_id),
            ContentTypeEnum::from($model->type),
            $model->value,
        );
    }

    #[Override]
    public function toEloquent(object $entity): ContentModel
    {
        if (! $entity instanceof Content) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', Content::class, $entity::class));
        }

        $model = new ContentModel();
        $model->id = $entity->id()->value();
        $model->source_id = $entity->sourceId()->value();
        $model->type = $entity->type()->value;
        $model->value = $entity->value();

        return $model;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @return array{
     *     id: string,
     *     source_id: string,
     *     type: string,
     *     value: string
     * }
     */
    public function toPersistenceState(Content $entity): array
    {
        return [
            'id' => $entity->id()->value(),
            'source_id' => $entity->sourceId()->value(),
            'type' => $entity->type()->value,
            'value' => $entity->value(),
        ];
    }

    public function toDto(ContentModel $model): ContentDto
    {
        return new ContentDto(
            id: $model->id,
            type: $model->type,
            value: $model->value,
        );
    }
}
