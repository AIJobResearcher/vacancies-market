<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

use App\Domain\Entities\Vacancy;
use App\Domain\Entities\VacancyJobAssignment;
use App\Domain\Entities\VacancyRequirementAssignment;
use App\Domain\Entities\VacancySource;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\EntityIds\VacancyJobAssignmentId;
use App\Domain\ValueObjects\EntityIds\VacancyRequirementAssignmentId;
use App\Domain\ValueObjects\EntityIds\VacancySourceId;
use App\Domain\ValueObjects\ExternalUrls;
use App\Domain\ValueObjects\Salary;
use App\Infrastructure\Eloquents\Models\VacancyJobAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use App\Infrastructure\Eloquents\Models\VacancyRequirementAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancySourceModel;
use DateTimeImmutable;
use InvalidArgumentException;
use Override;

final class VacancyMapper extends AbstractMapper
{
    #[Override]
    public function toDomain(object $model): Vacancy
    {
        if (! $model instanceof VacancyModel) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', VacancyModel::class, $model::class));
        }

        $requirementAssignments = [];
        if ($model->relationLoaded('requirementAssignments')) {
            $requirementAssignments = $model->requirementAssignments
                ->map(
                    static function (VacancyRequirementAssignmentModel $row): VacancyRequirementAssignment {
                        return new VacancyRequirementAssignment(
                            VacancyRequirementAssignmentId::fromString($row->id),
                            VacancyId::fromString($row->vacancy_id),
                            RequirementId::fromString($row->requirement_id),
                            $row->assigned_at,
                            $row->version,
                        );
                    },
                )
                ->all();
        }

        $jobAssignments = [];
        if ($model->relationLoaded('jobAssignments')) {
            $jobAssignments = $model->jobAssignments
                ->map(
                    static function (VacancyJobAssignmentModel $row): VacancyJobAssignment {
                        return new VacancyJobAssignment(
                            VacancyJobAssignmentId::fromString($row->id),
                            VacancyId::fromString($row->vacancy_id),
                            JobId::fromString($row->job_id),
                            $row->assigned_at,
                            $row->relevance_score,
                            $row->version,
                            $row->unassigned_at,
                        );
                    },
                )
                ->all();
        }

        $sources = [];
        if ($model->relationLoaded('sources')) {
            $sources = $model->sources
                ->map(
                    static function (VacancySourceModel $row): VacancySource {
                        return new VacancySource(
                            VacancySourceId::fromString($row->id),
                            VacancyId::fromString($row->vacancy_id),
                            $row->source_key,
                            $row->external_vacancy_id,
                            $row->external_url,
                            $row->first_seen_at,
                            $row->last_seen_at,
                            $row->closed_at,
                            $row->is_primary,
                        );
                    },
                )
                ->all();
        }

        return Vacancy::reconstitute(
            id: VacancyId::fromString($model->id),
            employerId: EmployerId::fromString($model->employer_id),
            title: $model->title,
            description: $model->description,
            salary: new Salary(
                $model->salary_min,
                $model->salary_max,
                $model->salary_currency,
            ),
            status: VacancyStatusEnum::from($model->status),
            country: $model->country,
            city: $model->city,
            employmentType: EmploymentTypeEnum::from($model->employment_type),
            workplace: WorkplaceEnum::from($model->workplace),
            postedAt: $model->posted_at,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            closedAt: $model->closed_at,
            version: $model->version,
            externalUrls: new ExternalUrls($model->external_urls),
            internalUrl: $model->internal_url,
            requirementAssignments: $requirementAssignments,
            jobAssignments: $jobAssignments,
            sources: $sources,
        );
    }

    #[Override]
    public function toEloquent(object $entity): VacancyModel
    {
        if (! $entity instanceof Vacancy) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', Vacancy::class, $entity::class));
        }

        $salary = $entity->salary();

        $model = new VacancyModel();
        $model->id = $entity->id()->value();
        $model->employer_id = $entity->employerId()->value();
        $model->title = $entity->title();
        $model->description = $entity->description();
        $model->salary_min = $salary->min();
        $model->salary_max = $salary->max();
        $model->salary_currency = $salary->currency();
        $model->status = $entity->status();
        $model->country = $entity->country();
        $model->city = $entity->city();
        $model->employment_type = $entity->employmentType()->value;
        $model->workplace = $entity->workplace()->value;
        $model->posted_at = $entity->postedAt();
        $model->closed_at = $entity->closedAt();
        $model->version = $entity->version();
        $model->external_urls = array_values($entity->externalUrls()->toArray());
        $model->internal_url = $entity->internalUrl();

        return $model;
    }

    /**
     * @return array{
     *     id: string,
     *     employer_id: string,
     *     title: string,
     *     description: string|null,
     *     salary_min: int,
     *     salary_max: int|null,
     *     salary_currency: string,
     *     status: string,
     *     country: string|null,
     *     city: string|null,
     *     employment_type: string,
     *     workplace: string,
     *     posted_at: DateTimeImmutable,
     *     closed_at: DateTimeImmutable|null,
     *     version: int,
     *     external_urls: string[],
     *     internal_url: string|null
     * }
     */
    public function toPersistenceState(Vacancy $entity): array
    {
        $salary = $entity->salary();

        return [
            'id' => $entity->id()->value(),
            'employer_id' => $entity->employerId()->value(),
            'title' => $entity->title(),
            'description' => $entity->description(),
            'salary_min' => $salary->min(),
            'salary_max' => $salary->max(),
            'salary_currency' => $salary->currency(),
            'status' => $entity->status(),
            'country' => $entity->country(),
            'city' => $entity->city(),
            'employment_type' => $entity->employmentType()->value,
            'workplace' => $entity->workplace()->value,
            'posted_at' => $entity->postedAt(),
            'closed_at' => $entity->closedAt(),
            'version' => $entity->version(),
            'external_urls' => array_values($entity->externalUrls()->toArray()),
            'internal_url' => $entity->internalUrl(),
        ];
    }
}
