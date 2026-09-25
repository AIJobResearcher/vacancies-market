<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Domain\DTOs\InterviewerSummaryDto;
use App\Domain\DTOs\VacancyDetailDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

final class GetVacancyByIdResource extends JsonResource
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
     *     description: string|null,
     *     requirements: list<string>,
     *     internal_url: string|null,
     *     external_urls: string[],
     *     employer: array{
     *         id: string,
     *         title: string,
     *         description: string|null,
     *         website: string|null,
     *         email: string|null,
     *         phone: string|null,
     *         logo_url: string|null,
     *     },
     *     interviewer: array{
     *         id: string,
     *         full_name: string,
     *         position: string|null,
     *         profile_urls: array<string, string>|null,
     *     }|null,
     *     closed_at: string|null,
     *     created_at: string,
     *     updated_at: string,
     *     version: int,
     * }
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var VacancyDetailDto $vacancy */
        $vacancy = $this->resource;

        $employer = $vacancy->employer;

        return [
            'id' => $vacancy->id,
            'title' => $vacancy->title,
            'employer_id' => $employer->id,
            'employer_title' => $employer->title,
            'min_salary' => $vacancy->minSalary,
            'max_salary' => $vacancy->maxSalary,
            'country' => $vacancy->country,
            'city' => $vacancy->city,
            'employment_type' => $vacancy->employmentType,
            'workplace' => $vacancy->workplace,
            'status' => $vacancy->status,
            'posted_at' => $vacancy->postedAt->format(DATE_ATOM),
            'description' => $vacancy->description,
            'requirements' => $vacancy->requirements,
            'internal_url' => $vacancy->internalUrl,
            'external_urls' => $vacancy->externalUrls,
            'employer' => [
                'id' => $employer->id,
                'title' => $employer->title,
                'description' => $employer->description,
                'website' => $employer->website,
                'email' => $employer->email,
                'phone' => $employer->phone,
                'logo_url' => $employer->logoUrl,
            ],
            'interviewer' => $this->interviewerPayload($vacancy->interviewer),
            'closed_at' => $vacancy->closedAt?->format(DATE_ATOM),
            'created_at' => $vacancy->createdAt->format(DATE_ATOM),
            'updated_at' => $vacancy->updatedAt->format(DATE_ATOM),
            'version' => $vacancy->version,
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     full_name: string,
     *     position: string|null,
     *     profile_urls: array<string, string>|null,
     * }|null
     */
    private function interviewerPayload(?InterviewerSummaryDto $interviewer): ?array
    {
        if ($interviewer === null) {
            return null;
        }

        return [
            'id' => $interviewer->id,
            'full_name' => $interviewer->fullName,
            'position' => $interviewer->position,
            'profile_urls' => $interviewer->profileUrls,
        ];
    }
}
