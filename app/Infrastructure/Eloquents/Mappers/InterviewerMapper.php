<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

use App\Domain\Entities\Interviewer;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\InterviewerContacts;
use App\Infrastructure\Eloquents\Models\InterviewerModel;
use DateTimeImmutable;
use InvalidArgumentException;
use Override;

final class InterviewerMapper extends AbstractMapper
{
    #[Override]
    public function toDomain(object $model): Interviewer
    {
        if (! $model instanceof InterviewerModel) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', InterviewerModel::class, $model::class));
        }

        return Interviewer::reconstitute(
            id: InterviewerId::fromString($model->id),
            fullName: $model->full_name,
            position: $model->position,
            contacts: $model->contacts === null ? null : InterviewerContacts::fromArray($model->contacts),
            avatarUrl: $model->avatar_url,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            version: $model->version,
            deletedAt: $model->deleted_at,
        );
    }

    #[Override]
    public function toEloquent(object $entity): InterviewerModel
    {
        if (! $entity instanceof Interviewer) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', Interviewer::class, $entity::class));
        }

        $model = new InterviewerModel();
        $model->id = $entity->id()->value();
        $model->full_name = $entity->fullName();
        $model->position = $entity->position();
        $model->contacts = $entity->contacts()?->toArray();
        $model->avatar_url = $entity->avatarUrl();
        $model->is_active = $entity->isActive();
        $model->version = $entity->version();
        $model->deleted_at = $entity->deletedAt();

        return $model;
    }

    /**
     * @return array{
     *     id: string,
     *     full_name: string,
     *     position: string|null,
     *     contacts: list<array{type: string, value: string}>|null,
     *     avatar_url: string|null,
     *     is_active: bool,
     *     version: int,
     *     deleted_at: DateTimeImmutable|null
     * }
     */
    public function toPersistenceState(Interviewer $entity): array
    {
        return [
            'id' => $entity->id()->value(),
            'full_name' => $entity->fullName(),
            'position' => $entity->position(),
            'contacts' => $entity->contacts()?->toArray(),
            'avatar_url' => $entity->avatarUrl(),
            'is_active' => $entity->isActive(),
            'version' => $entity->version(),
            'deleted_at' => $entity->deletedAt(),
        ];
    }
}
