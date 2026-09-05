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
            name: $model->name,
            baseUrl: $model->base_url,
            apiEndpoint: $model->api_endpoint,
            crawlDelaySeconds: $model->crawl_delay_seconds,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            version: $model->version,
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
        $model->name = $entity->name();
        $model->base_url = $entity->baseUrl();
        $model->api_endpoint = $entity->apiEndpoint();
        $model->crawl_delay_seconds = $entity->crawlDelaySeconds();
        $model->version = $entity->version();

        return $model;
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     base_url: string,
     *     api_endpoint: string|null,
     *     crawl_delay_seconds: int,
     *     version: int
     * }
     */
    public function toPersistenceState(Portal $entity): array
    {
        return [
            'id' => $entity->id()->value(),
            'name' => $entity->name(),
            'base_url' => $entity->baseUrl(),
            'api_endpoint' => $entity->apiEndpoint(),
            'crawl_delay_seconds' => $entity->crawlDelaySeconds(),
            'version' => $entity->version(),
        ];
    }
}
