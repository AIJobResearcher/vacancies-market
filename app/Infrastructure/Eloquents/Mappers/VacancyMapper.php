<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Mappers;

use App\Domain\DTOs\InterviewerSummaryDto;
use App\Domain\DTOs\RequirementSummaryDto;
use App\Domain\DTOs\VacancyDetailDto;
use App\Domain\DTOs\VacancyPreviewDto;
use App\Domain\Entities\Source;
use App\Domain\Entities\Vacancy;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Infrastructure\Eloquents\Models\InterviewerModel;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use App\Infrastructure\Eloquents\Models\SourceModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use DateTimeImmutable;
use InvalidArgumentException;
use Override;

final class VacancyMapper extends AbstractMapper
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private readonly SourceMapper $sourceMapper)
    {
    }

    #[Override]
    public function toDomain(object $model): Vacancy
    {
        if (! $model instanceof VacancyModel) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', VacancyModel::class, $model::class));
        }

        return Vacancy::reconstitute(
            id: VacancyId::fromString($model->id),
            employerId: EmployerId::fromString($model->employer_id),
            title: $model->title,
            minSalary: $model->min_salary,
            maxSalary: $model->max_salary,
            status: VacancyStatusEnum::from($model->status),
            employmentTypes: $this->employmentTypes($model->employment_types),
            workplaces: $this->workplaces($model->workplaces),
            researcherLocationIds: $model->researcher_location_ids,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            closedAt: $model->closed_at,
            version: $model->version,
            requirementIds: $this->requirementIds($model),
            jobIds: $this->jobIds($model),
            sources: $this->sources($model),
        );
    }

    #[Override]
    public function toEloquent(object $entity): VacancyModel
    {
        if (! $entity instanceof Vacancy) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s.', Vacancy::class, $entity::class));
        }

        $model = new VacancyModel();
        $model->id = $entity->id()->value();
        $model->employer_id = $entity->employerId()->value();
        $model->title = $entity->title();
        $model->min_salary = $entity->minSalary();
        $model->max_salary = $entity->maxSalary();
        $model->status = $entity->status();
        $model->employment_types = array_map(
            static fn (EmploymentTypeEnum $type): string => $type->value,
            $entity->employmentTypes(),
        );
        $model->workplaces = array_map(
            static fn (WorkplaceEnum $workplace): string => $workplace->value,
            $entity->workplaces(),
        );
        $model->researcher_location_ids = $entity->researcherLocationIds();
        $model->closed_at = $entity->closedAt();
        $model->version = $entity->version();

        return $model;
    }

    /**
     * @return array{
     *     id: string,
     *     employer_id: string,
     *     title: string,
     *     min_salary: int,
     *     max_salary: int|null,
     *     status: string,
     *     employment_types: list<string>,
     *     workplaces: list<string>,
     *     researcher_location_ids: list<int>,
     *     closed_at: DateTimeImmutable|null,
     *     version: int
     * }
     */
    public function toPersistenceState(Vacancy $entity): array
    {
        return [
            'id' => $entity->id()->value(),
            'employer_id' => $entity->employerId()->value(),
            'title' => $entity->title(),
            'min_salary' => $entity->minSalary(),
            'max_salary' => $entity->maxSalary(),
            'status' => $entity->status(),
            'employment_types' => array_map(
                static fn (EmploymentTypeEnum $type): string => $type->value,
                $entity->employmentTypes(),
            ),
            'workplaces' => array_map(
                static fn (WorkplaceEnum $workplace): string => $workplace->value,
                $entity->workplaces(),
            ),
            'researcher_location_ids' => $entity->researcherLocationIds(),
            'closed_at' => $entity->closedAt(),
            'version' => $entity->version(),
        ];
    }

    /**
     * Builds the preview read model from a model selected through
     * `VacancyEloquentRepository::PREVIEW_COLUMNS`.
     */
    public function toPreviewDto(VacancyModel $model): VacancyPreviewDto
    {
        return new VacancyPreviewDto(
            id: $model->id,
            title: $model->title,
            employerId: $model->employer_id,
            employerTitle: $model->employer_title,
            minSalary: $model->min_salary,
            maxSalary: $model->max_salary,
            researcherLocationIds: $model->researcher_location_ids,
            employmentTypes: $model->employment_types,
            workplaces: $model->workplaces,
            status: $model->status,
        );
    }

    /**
     * Builds the detail read model from the vacancy row joined with its
     * employer, plus the separately loaded relation rows.
     *
     * @param array<int, RequirementModel> $requirements
     * @param array<int, InterviewerModel> $interviewers
     * @param array<int, SourceModel> $sources
     */
    public function toDetailDto(
        VacancyModel $model,
        array $requirements,
        array $interviewers,
        array $sources,
    ): VacancyDetailDto {
        $requirementDtos = [];
        foreach ($requirements as $requirement) {
            $requirementDtos[] = new RequirementSummaryDto(
                id: $requirement->id,
                title: $requirement->title,
            );
        }

        $interviewerDtos = [];
        foreach ($interviewers as $interviewer) {
            $interviewerDtos[] = new InterviewerSummaryDto(
                id: $interviewer->id,
                fullName: $interviewer->full_name,
                position: $interviewer->position,
                contacts: $interviewer->contacts,
                avatarUrl: $interviewer->avatar_url,
            );
        }

        $sourceDtos = [];
        foreach ($sources as $source) {
            $sourceDtos[] = $this->sourceMapper->toDto($source);
        }

        return new VacancyDetailDto(
            preview: $this->toPreviewDto($model),
            requirements: $requirementDtos,
            interviewers: $interviewerDtos,
            sources: $sourceDtos,
            closedAt: $model->closed_at,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            version: $model->version,
        );
    }

    /**
     * @param list<string> $values
     * @return list<EmploymentTypeEnum>
     */
    private function employmentTypes(array $values): array
    {
        $types = [];
        foreach ($values as $value) {
            $types[] = EmploymentTypeEnum::from($value);
        }

        return $types;
    }

    /** @return list<RequirementId> */
    private function requirementIds(VacancyModel $model): array
    {
        if (! $model->relationLoaded('requirements')) {
            return [];
        }

        $requirementIds = [];
        foreach ($model->requirements as $row) {
            $requirementIds[] = RequirementId::fromString($row->id);
        }

        return $requirementIds;
    }

    /** @return list<JobId> */
    private function jobIds(VacancyModel $model): array
    {
        if (! $model->relationLoaded('jobs')) {
            return [];
        }

        $jobIds = [];
        foreach ($model->jobs as $row) {
            $jobIds[] = JobId::fromString($row->id);
        }

        return $jobIds;
    }

    /** @return list<Source> */
    private function sources(VacancyModel $model): array
    {
        if (! $model->relationLoaded('sources')) {
            return [];
        }

        $sources = [];
        foreach ($model->sources as $row) {
            $sources[] = $this->sourceMapper->toDomain($row);
        }

        return $sources;
    }

    /**
     * @param list<string> $values
     * @return list<WorkplaceEnum>
     */
    private function workplaces(array $values): array
    {
        $workplaces = [];
        foreach ($values as $value) {
            $workplaces[] = WorkplaceEnum::from($value);
        }

        return $workplaces;
    }
}
