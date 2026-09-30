<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\DTOs\GetVacanciesByJobIdFilterDto;
use App\Domain\DTOs\VacancyDetailDto;
use App\Domain\DTOs\VacancyPreviewPageDto;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Infrastructure\Eloquents\Mappers\VacancyMapper;
use App\Infrastructure\Eloquents\Models\InterviewerModel;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use App\Infrastructure\Eloquents\Models\SourceModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Override;

final class VacancyEloquentRepository implements VacancyRepositoryInterface
{
    private const PREVIEW_COLUMNS = [
        'vacancies.id',
        'vacancies.title',
        'vacancies.employer_id',
        'employers.title as employer_title',
        'vacancies.min_salary',
        'vacancies.max_salary',
        'vacancies.researcher_location_ids',
        'vacancies.employment_types',
        'vacancies.workplaces',
        'vacancies.status',
    ];

    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private readonly VacancyMapper $mapper)
    {
    }

    #[Override]
    public function searchPreviews(
        GetVacanciesByJobIdFilterDto $filter,
        int $page,
        int $perPage,
    ): VacancyPreviewPageDto {
        $query = VacancyModel::query();

        $this->applyPreviewFilters($query, $filter);

        $query->join('employers', 'employers.id', '=', 'vacancies.employer_id');
        $query->select(self::PREVIEW_COLUMNS);
        $query->orderByDesc('vacancies.created_at');
        $query->orderBy('vacancies.id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];

        foreach ($paginator->getCollection() as $vacancy) {
            $items[] = $this->mapper->toPreviewDto($vacancy);
        }

        return new VacancyPreviewPageDto($items, $paginator->total());
    }

    #[Override]
    public function findDetailById(VacancyId $id): ?VacancyDetailDto
    {
        $query = VacancyModel::query();

        $query->join('employers', 'employers.id', '=', 'vacancies.employer_id');
        $query->select([
            ...self::PREVIEW_COLUMNS,
            'vacancies.closed_at',
            'vacancies.created_at',
            'vacancies.updated_at',
            'vacancies.version',
        ]);

        $query->where('vacancies.id', $id->value());

        $model = $query->first();

        if ($model === null) {
            return null;
        }

        return $this->mapper->toDetailDto(
            $model,
            $this->findRequirementSummaries($id),
            $this->findInterviewerSummaries($model->employer_id),
            $this->findSources($id),
        );
    }

    /** @param Builder<VacancyModel> $query */
    private function applyPreviewFilters(Builder $query, GetVacanciesByJobIdFilterDto $filter): void
    {
        $query->join(
            'vacancy_job_assignments',
            'vacancy_job_assignments.vacancy_id',
            '=',
            'vacancies.id',
        );
        $query->where('vacancy_job_assignments.job_id', $filter->jobId->value());

        if ($filter->employerIds !== []) {
            $query->whereIn('vacancies.employer_id', array_map(
                static fn (EmployerId $employerId): string => $employerId->value(),
                $filter->employerIds,
            ));
        }

        if ($filter->locationIds !== []) {
            $this->applyJsonOverlapFilter($query, 'vacancies.researcher_location_ids', $filter->locationIds);
        }

        if ($filter->minSalary !== null) {
            $query->where('vacancies.min_salary', '>=', $filter->minSalary);
        }

        if ($filter->maxSalary !== null) {
            $query->where('vacancies.max_salary', '<=', $filter->maxSalary);
        }

        if ($filter->status !== null) {
            $query->where('vacancies.status', $filter->status->value);
        }

        if ($filter->workplaces !== []) {
            $this->applyJsonOverlapFilter(
                $query,
                'vacancies.workplaces',
                array_map(
                    static fn (WorkplaceEnum $workplace): string => $workplace->value,
                    $filter->workplaces,
                ),
            );
        }

        if ($filter->employmentTypes !== []) {
            $this->applyJsonOverlapFilter(
                $query,
                'vacancies.employment_types',
                array_map(
                    static fn (EmploymentTypeEnum $type): string => $type->value,
                    $filter->employmentTypes,
                ),
            );
        }

        if ($filter->postedFrom !== null || $filter->postedTo !== null) {
            $this->applyPostedAtFilter($query, $filter);
        }
    }

    /**
     * @param Builder<VacancyModel> $query
     * @param list<int|string> $values
     */
    private function applyJsonOverlapFilter(Builder $query, string $column, array $values): void
    {
        $query->where(static function (Builder $nested) use ($column, $values): void {
            foreach ($values as $value) {
                $nested->orWhereJsonContains($column, [$value]);
            }
        });
    }

    /** @param Builder<VacancyModel> $query */
    private function applyPostedAtFilter(Builder $query, GetVacanciesByJobIdFilterDto $filter): void
    {
        $query->whereExists(static function (QueryBuilder $sources) use ($filter): void {
            $sources->selectRaw('1');
            $sources->from('sources');
            $sources->whereColumn('sources.vacancy_id', 'vacancies.id');

            if ($filter->postedFrom !== null) {
                $sources->where('sources.posted_at', '>=', $filter->postedFrom);
            }

            if ($filter->postedTo !== null) {
                $sources->where('sources.posted_at', '<=', $filter->postedTo);
            }
        });
    }

    /** @return array<int, RequirementModel> */
    private function findRequirementSummaries(VacancyId $id): array
    {
        $query = RequirementModel::query();

        $query->join(
            'vacancy_requirement_assignments',
            'vacancy_requirement_assignments.requirement_id',
            '=',
            'requirements.id',
        );
        $query->where('vacancy_requirement_assignments.vacancy_id', $id->value());
        $query->orderBy('requirements.title');

        return $query->get(['requirements.id', 'requirements.title'])->all();
    }

    /** @return array<int, InterviewerModel> */
    private function findInterviewerSummaries(string $employerId): array
    {
        $query = InterviewerModel::query();

        $query->where('employer_id', $employerId);
        $query->whereNull('deleted_at');
        $query->orderBy('full_name');

        return $query->get(['id', 'full_name', 'position', 'contacts', 'avatar_url'])->all();
    }

    /** @return array<int, SourceModel> */
    private function findSources(VacancyId $id): array
    {
        $query = SourceModel::query();

        $query->where('vacancy_id', $id->value());
        $query->with('contents');
        $query->orderBy('posted_at');

        return $query->get()->all();
    }
}
