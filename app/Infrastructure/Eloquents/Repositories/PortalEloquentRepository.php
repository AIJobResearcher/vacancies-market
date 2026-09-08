<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Entities\Portal;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\PortalRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Infrastructure\Eloquents\Mappers\PortalMapper;
use App\Infrastructure\Eloquents\Models\PortalModel;
use Override;

final class PortalEloquentRepository implements PortalRepositoryInterface
{
    public function __construct(private readonly PortalMapper $mapper)
    {
    }

    #[Override]
    public function findById(PortalId $id): ?Portal
    {
        $model = PortalModel::query()->find($id->value());

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    #[Override]
    public function save(Portal $portal): void
    {
        $state = $this->mapper->toPersistenceState($portal);

        if (PortalModel::query()->whereKey($portal->id()->value())->exists()) {
            $expected = $portal->version() - 1;
            $affected = PortalModel::query()
                ->whereKey($portal->id()->value())
                ->where('version', $expected)
                ->update($state);

            if ($affected === 0) {
                $existing = PortalModel::query()
                    ->whereKey($portal->id()->value())
                    ->first(['version']);

                $actual = $existing === null ? 0 : $existing->version;

                throw new VersionConflictException(
                    'Portal',
                    $portal->id()->value(),
                    $expected,
                    $actual,
                );
            }
        } else {
            PortalModel::query()->create($state);
        }
    }
}
