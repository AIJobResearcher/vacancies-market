<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

use App\Domain\Entities\Interviewer;
use App\Domain\Entities\InterviewerVacancyAssignment;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\EntityIds\InterviewerVacancyAssignmentId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Infrastructure\Eloquents\Models\InterviewerModel;
use App\Infrastructure\Eloquents\Models\InterviewerVacancyAssignmentModel;
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

        $assignments = [];
        if ($model->relationLoaded('vacancyAssignments')) {
            $assignments = $model->vacancyAssignments
                ->map(
                    static function (InterviewerVacancyAssignmentModel $row): InterviewerVacancyAssignment {
                        return new InterviewerVacancyAssignment(
                            InterviewerVacancyAssignmentId::fromString($row->id),
                            InterviewerId::fromString($row->interviewer_id),
                            VacancyId::fromString($row->vacancy_id),
                            $row->assigned_at,
                            $row->unassigned_at,
                            $row->version,
                        );
                    },
                )
                ->all();
        }

        return Interviewer::reconstitute(
            id: InterviewerId::fromString($model->id),
            employerId: EmployerId::fromString($model->employer_id),
            fullName: $model->full_name,
            position: $model->position,
            profileUrls: $model->profile_urls,
            isActive: $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            version: $model->version,
            deletedAt: $model->deleted_at,
            vacancyAssignments: $assignments,
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
        $model->employer_id = $entity->employerId()->value();
        $model->full_name = $entity->fullName();
        $model->position = $entity->position();
        $model->profile_urls = $entity->profileUrls();
        $model->is_active = $entity->isActive();
        $model->version = $entity->version();
        $model->deleted_at = $entity->deletedAt();

        return $model;
    }

    /**
     * @return array{
     *     id: string,
     *     employer_id: string,
     *     full_name: string,
     *     position: string|null,
     *     profile_urls: array<string, string>|null,
     *     is_active: bool,
     *     version: int,
     *     deleted_at: DateTimeImmutable|null
     * }
     */
    public function toPersistenceState(Interviewer $entity): array
    {
        return [
            'id' => $entity->id()->value(),
            'employer_id' => $entity->employerId()->value(),
            'full_name' => $entity->fullName(),
            'position' => $entity->position(),
            'profile_urls' => $entity->profileUrls(),
            'is_active' => $entity->isActive(),
            'version' => $entity->version(),
            'deleted_at' => $entity->deletedAt(),
        ];
    }
}
