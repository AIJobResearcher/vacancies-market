<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Entities\Requirement;
use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Infrastructure\Eloquents\Mappers\RequirementMapper;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use Override;

final class RequirementEloquentRepository implements RequirementRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private readonly RequirementMapper $mapper)
    {
    }

    #[Override]
    public function findById(RequirementId $id): ?Requirement
    {
        $model = RequirementModel::query()->find($id->value());

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    #[Override]
    public function findByTitleCaseInsensitive(string $title): ?Requirement
    {
        $model = RequirementModel::query()
            ->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    #[Override]
    public function save(Requirement $requirement): void
    {
        RequirementModel::query()->updateOrCreate(
            ['id' => $requirement->id()->value()],
            $this->mapper->toPersistenceState($requirement),
        );
    }
}
