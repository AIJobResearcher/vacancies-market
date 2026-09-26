<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

use App\Domain\Entities\Employer;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Infrastructure\Eloquents\Models\EmployerModel;
use InvalidArgumentException;
use Override;

final class EmployerMapper extends AbstractMapper
{
    #[Override]
    public function toDomain(object $model): Employer
    {
        if (! $model instanceof EmployerModel) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', EmployerModel::class, $model::class));
        }

        return Employer::reconstitute(
            id: EmployerId::fromString($model->id),
            title: $model->title,
            description: $model->description,
            website: $model->website,
            email: $model->email,
            phone: $model->phone,
            logoUrl: $model->logo_url,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            version: $model->version,
        );
    }

    #[Override]
    public function toEloquent(object $entity): EmployerModel
    {
        if (! $entity instanceof Employer) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', Employer::class, $entity::class));
        }

        $model = new EmployerModel();
        $model->id = $entity->id()->value();
        $model->title = $entity->title();
        $model->description = $entity->description();
        $model->website = $entity->website();
        $model->email = $entity->email();
        $model->phone = $entity->phone();
        $model->logo_url = $entity->logoUrl();
        $model->version = $entity->version();

        return $model;
    }

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     description: string|null,
     *     website: string|null,
     *     email: string|null,
     *     phone: string|null,
     *     logo_url: string|null,
     *     version: int
     * }
     */
    public function toPersistenceState(Employer $entity): array
    {
        return [
            'id' => $entity->id()->value(),
            'title' => $entity->title(),
            'description' => $entity->description(),
            'website' => $entity->website(),
            'email' => $entity->email(),
            'phone' => $entity->phone(),
            'logo_url' => $entity->logoUrl(),
            'version' => $entity->version(),
        ];
    }
}
