<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\DTOs\GetVacanciesByJobIdFilterDto;
use App\Domain\DTOs\VacancyPreviewDto;
use App\Domain\Repositories\VacancyRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetVacanciesByJobIdUseCase
{
    private const DEFAULT_PAGE = 1;

    private const DEFAULT_PER_PAGE = 20;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private readonly VacancyRepositoryInterface $vacancyRepository)
    {
    }

    /**
     * @return LengthAwarePaginator<int, VacancyPreviewDto>
     */
    public function handle(GetVacanciesByJobIdFilterDto $filter): LengthAwarePaginator
    {
        $page = $filter->page ?? self::DEFAULT_PAGE;
        $perPage = $filter->perPage ?? self::DEFAULT_PER_PAGE;

        $result = $this->vacancyRepository->searchPreviews($filter, $page, $perPage);

        return new LengthAwarePaginator($result->items, $result->total, $perPage, $page);
    }
}
