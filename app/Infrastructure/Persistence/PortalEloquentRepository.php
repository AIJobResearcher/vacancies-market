<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Portal;
use App\Domain\Repositories\PortalRepositoryInterface;
use App\Infrastructure\Models\Portal as PortalModel;

final class PortalEloquentRepository implements PortalRepositoryInterface
{
    public function save(Portal $portal): void
    {
        PortalModel::updateOrCreate(
            ['id' => $portal->id],
            [
                'name' => $portal->name,
                'base_url' => $portal->baseUrl,
                'api_endpoint' => $portal->apiEndpoint,
                'parsing_config' => $portal->parsingConfig,
                'crawl_delay_seconds' => $portal->crawlDelaySeconds,
            ]
        );
    }

    public function findById(string $id): ?Portal
    {
        $model = PortalModel::find($id);
        if (!$model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function all(): array
    {
        return PortalModel::all()
            ->map(fn ($model) => $this->toDomain($model))
            ->toArray();
    }

    public function remove(string $id): void
    {
        PortalModel::destroy($id);
    }

    private function toDomain(PortalModel $model): Portal
    {
        return new Portal(
            $model->id,
            $model->name,
            $model->base_url,
            $model->api_endpoint,
            $model->parsing_config ?? [],
            $model->crawl_delay_seconds,
            $model->created_at,
            $model->updated_at,
        );
    }
}

