<?php

declare(strict_types=1);

namespace App\Application\Queries;

use App\Application\DTOs\SearchVacanciesCriteria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VacancySearchQueryInterface
{
    public function search(SearchVacanciesCriteria $criteria): LengthAwarePaginator;
}
