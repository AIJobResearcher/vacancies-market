<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Domain\DTOs\VacancyPreviewDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

final class GetVacanciesByJobIdResource extends JsonResource
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     * @return array{
     *     id: string,
     *     title: string,
     *     employer: array{employer_id: string, employer_title: string},
     *     min_salary: int,
     *     max_salary: int|null,
     *     researcher_location_ids: list<int>,
     *     employment_types: list<string>,
     *     workplaces: list<string>,
     *     status: string,
     * }
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var VacancyPreviewDto $preview */
        $preview = $this->resource;

        return [
            'id' => $preview->id,
            'title' => $preview->title,
            'employer' => [
                'employer_id' => $preview->employerId,
                'employer_title' => $preview->employerTitle,
            ],
            'min_salary' => $preview->minSalary,
            'max_salary' => $preview->maxSalary,
            'researcher_location_ids' => $preview->researcherLocationIds,
            'employment_types' => $preview->employmentTypes,
            'workplaces' => $preview->workplaces,
            'status' => $preview->status,
        ];
    }
}
