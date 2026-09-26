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
     *     employer_id: string,
     *     employer_title: string,
     *     min_salary: int,
     *     max_salary: int|null,
     *     country: string|null,
     *     city: string|null,
     *     employment_type: string,
     *     workplace: string,
     *     status: string,
     *     posted_at: string,
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
            'employer_id' => $preview->employerId,
            'employer_title' => $preview->employerTitle,
            'min_salary' => $preview->minSalary,
            'max_salary' => $preview->maxSalary,
            'country' => $preview->country,
            'city' => $preview->city,
            'employment_type' => $preview->employmentType,
            'workplace' => $preview->workplace,
            'status' => $preview->status,
            'posted_at' => $preview->postedAt->format(DATE_ATOM),
        ];
    }
}
