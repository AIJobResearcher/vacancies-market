<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Queries;

use App\Application\DTOs\SearchVacanciesCriteria;
use App\Application\Enums\VacancySort;
use App\Application\Queries\VacancySearchQueryInterface;
use App\Infrastructure\Models\Vacancy as VacancyModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentVacancySearchQuery implements VacancySearchQueryInterface
{
    public function __construct(
        private readonly VacancyPreviewDataMapper $vacancyPreviewDataMapper
    ) {}

    public function search(SearchVacanciesCriteria $criteria): LengthAwarePaginator
    {
        $query = VacancyModel::query()
            ->leftJoin('employers', 'employers.id', '=', 'vacancies.employer_id')
            ->select('vacancies.*', 'employers.name as employer_name');

        $this->applyFilters($query, $criteria);
        $this->applySorting($query, $criteria->sort);

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page
        );

        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn (VacancyModel $model) => $this->vacancyPreviewDataMapper->map($model)
            )
        );

        return $paginator;
    }

    private function applyFilters(Builder $query, SearchVacanciesCriteria $criteria): void
    {
        if ($criteria->query !== null) {
            $query->where(function (Builder $innerQuery) use ($criteria): void {
                $innerQuery->where('title', 'like', '%'.$criteria->query.'%')
                    ->orWhere('description', 'like', '%'.$criteria->query.'%');
            });
        }

        if ($criteria->employerId !== null) {
            $query->where('employer_id', $criteria->employerId);
        }

        if ($criteria->country !== null) {
            $query->where('country', $criteria->country);
        }

        if ($criteria->city !== null) {
            $query->where('city', $criteria->city);
        }

        if ($criteria->salaryMin !== null) {
            $query->where('salary_max', '>=', $criteria->salaryMin);
        }

        if ($criteria->salaryMax !== null) {
            $query->where('salary_min', '<=', $criteria->salaryMax);
        }

        if ($criteria->status !== null) {
            $query->where('status', $criteria->status->value);
        }
    }

    private function applySorting(Builder $query, VacancySort $sort): void
    {
        match ($sort) {
            VacancySort::Date => $query->orderByDesc('created_at'),
            VacancySort::SalaryAsc => $query->orderBy('salary_min'),
            VacancySort::SalaryDesc => $query->orderByDesc('salary_max'),
            VacancySort::Relevance => $query->orderByDesc('updated_at'),
        };
    }
}
