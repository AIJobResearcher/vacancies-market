<?php
declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\DTOs\SearchVacanciesCriteria;
use App\Application\Queries\VacancySearchQueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class SearchVacanciesUseCase
{
    public function __construct(
        private readonly VacancySearchQueryInterface $vacancySearchQuery
    ) {
    }

    /**
     * Search vacancies by typed criteria and return paginated read-model data.
     */
    public function execute(SearchVacanciesCriteria $criteria): LengthAwarePaginator
    {
        return $this->vacancySearchQuery->search($criteria);
    }
}
