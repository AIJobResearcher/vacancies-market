<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\SearchVacanciesUseCase;
use App\Presentation\Http\Requests\SearchVacanciesRequest;
use App\Presentation\Http\Resources\VacancyPreviewCollection;

final class SearchVacanciesController extends Controller
{
    public function __construct(
        private readonly SearchVacanciesUseCase $searchVacanciesUseCase
    ) {
    }

    /**
     * Search vacancies with filtering and pagination.
     */
    public function __invoke(SearchVacanciesRequest $request): VacancyPreviewCollection
    {
        $criteria = $request->toCriteria();
        $paginator = $this->searchVacanciesUseCase->execute($criteria);

        return new VacancyPreviewCollection($paginator);
    }
}
