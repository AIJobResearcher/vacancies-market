<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

use App\Domain\DTOs\SourceDto;
use App\Domain\Entities\Source;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Domain\ValueObjects\EntityIds\SourceId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Infrastructure\Eloquents\Models\SourceModel;
use DateTimeImmutable;
use InvalidArgumentException;
use Override;

final class SourceMapper extends AbstractMapper
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private readonly ContentMapper $contentMapper)
    {
    }

    #[Override]
    public function toDomain(object $model): Source
    {
        if (! $model instanceof SourceModel) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', SourceModel::class, $model::class));
        }

        $contents = [];
        if ($model->relationLoaded('contents')) {
            foreach ($model->contents as $content) {
                $contents[] = $this->contentMapper->toDomain($content);
            }
        }

        return Source::reconstitute(
            SourceId::fromString($model->id),
            VacancyId::fromString($model->vacancy_id),
            PortalId::fromString($model->portal_id),
            $model->external_vacancy_id,
            $model->external_url,
            $model->title,
            $model->posted_at,
            $model->created_at,
            $model->updated_at,
            $contents,
        );
    }

    #[Override]
    public function toEloquent(object $entity): SourceModel
    {
        if (! $entity instanceof Source) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', Source::class, $entity::class));
        }

        $model = new SourceModel();
        $model->id = $entity->id()->value();
        $model->vacancy_id = $entity->vacancyId()->value();
        $model->portal_id = $entity->portalId()->value();
        $model->external_vacancy_id = $entity->externalVacancyId();
        $model->external_url = $entity->externalUrl();
        $model->title = $entity->title();
        $model->posted_at = $entity->postedAt();

        return $model;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @return array{
     *     id: string,
     *     vacancy_id: string,
     *     portal_id: string,
     *     external_vacancy_id: string|null,
     *     external_url: string,
     *     title: string,
     *     posted_at: DateTimeImmutable
     * }
     */
    public function toPersistenceState(Source $entity): array
    {
        return [
            'id' => $entity->id()->value(),
            'vacancy_id' => $entity->vacancyId()->value(),
            'portal_id' => $entity->portalId()->value(),
            'external_vacancy_id' => $entity->externalVacancyId(),
            'external_url' => $entity->externalUrl(),
            'title' => $entity->title(),
            'posted_at' => $entity->postedAt(),
        ];
    }

    public function toDto(SourceModel $model): SourceDto
    {
        $contents = [];
        foreach ($model->contents as $content) {
            $contents[] = $this->contentMapper->toDto($content);
        }

        return new SourceDto(
            id: $model->id,
            externalUrl: $model->external_url,
            title: $model->title,
            postedAt: $model->posted_at,
            contents: $contents,
        );
    }
}
