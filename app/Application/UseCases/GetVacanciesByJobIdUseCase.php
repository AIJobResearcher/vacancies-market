<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\DTOs\GetVacanciesByJobIdDto;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\VacancySearchCriteria;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetVacanciesByJobIdUseCase
{
    private const DEFAULT_PAGE = 1;

    private const DEFAULT_PER_PAGE = 20;

    public function __construct(private readonly VacancyRepositoryInterface $vacancyRepository)
    {
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function handle(GetVacanciesByJobIdDto $getListVacanciesDto): LengthAwarePaginator
    {
        $page = $getListVacanciesDto->page ?? self::DEFAULT_PAGE;
        $perPage = $getListVacanciesDto->perPage ?? self::DEFAULT_PER_PAGE;

        $result = $this->vacancyRepository->searchPreviews(
            $this->createCriteria($getListVacanciesDto),
            $page,
            $perPage,
        );

        return new LengthAwarePaginator($result['items'], $result['total'], $perPage, $page);
    }

    private function createCriteria(GetVacanciesByJobIdDto $getListVacanciesDto): VacancySearchCriteria
    {
        return new VacancySearchCriteria(
            jobId: JobId::fromString($getListVacanciesDto->jobId),
            employerId: $getListVacanciesDto->employerId === null
                ? null
                : EmployerId::fromString($getListVacanciesDto->employerId),
            country: $getListVacanciesDto->country,
            city: $getListVacanciesDto->city,
            minSalary: $getListVacanciesDto->minSalary,
            maxSalary: $getListVacanciesDto->maxSalary,
            status: $getListVacanciesDto->status,
            workplace: $getListVacanciesDto->workplace,
            employmentType: $getListVacanciesDto->employmentType,
            postedFrom: $getListVacanciesDto->postedFrom,
            postedTo: $getListVacanciesDto->postedTo,
        );
    }
}
