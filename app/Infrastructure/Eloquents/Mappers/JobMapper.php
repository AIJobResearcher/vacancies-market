<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

use App\Domain\Entities\Job;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\JobRequirementModel;
use DateTimeImmutable;
use InvalidArgumentException;
use Override;

final class JobMapper extends AbstractMapper
{
    #[Override]
    public function toDomain(object $model): Job
    {
        if (! $model instanceof JobModel) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', JobModel::class, $model::class));
        }

        $requirementIds = [];
        if ($model->relationLoaded('requirements')) {
            $requirementIds = $model->requirements
                ->map(
                    static fn(JobRequirementModel $row): RequirementId => RequirementId::fromString(
                        $row->requirement_id
                    ),
                )
                ->all();
        }

        return Job::reconstitute(
            id: JobId::fromString($model->id),
            title: $model->title,
            category: $model->category,
            subCategory: $model->sub_category,
            parentJobId: $model->parent_job_id === null ? null : JobId::fromString($model->parent_job_id),
            description: $model->description,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            version: $model->version,
            deletedAt: $model->deleted_at,
            requirementIds: $requirementIds,
        );
    }

    #[Override]
    public function toEloquent(object $entity): JobModel
    {
        if (!$entity instanceof Job) {
            throw new InvalidArgumentException(
                sprintf('Expected %s, got %s.', Job::class, $entity::class)
            );
        }

        $model = new JobModel();
        $model->id = $entity->id()->value();
        $model->title = $entity->title();
        $model->category = $entity->category();
        $model->sub_category = $entity->subCategory();
        $model->parent_job_id = $entity->parentJobId()?->value();
        $model->description = $entity->description();
        $model->version = $entity->version();
        $model->deleted_at = $entity->deletedAt();

        return $model;
    }

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     category: string|null,
     *     sub_category: string|null,
     *     parent_job_id: string|null,
     *     description: string|null,
     *     version: int,
     *     deleted_at: DateTimeImmutable|null
     * }
     */
    public function toPersistenceState(Job $entity): array
    {
        return [
            'id' => $entity->id()->value(),
            'title' => $entity->title(),
            'category' => $entity->category(),
            'sub_category' => $entity->subCategory(),
            'parent_job_id' => $entity->parentJobId()?->value(),
            'description' => $entity->description(),
            'version' => $entity->version(),
            'deleted_at' => $entity->deletedAt(),
        ];
    }
}
