<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Domain\DTOs\ContentDto;
use App\Domain\DTOs\InterviewerSummaryDto;
use App\Domain\DTOs\RequirementSummaryDto;
use App\Domain\DTOs\SourceDto;
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
     *     employer: array{employer_id: string, employer_title: string},
     *     min_salary: int,
     *     max_salary: int|null,
     *     researcher_location_ids: list<int>,
     *     employment_types: list<string>,
     *     workplaces: list<string>,
     *     status: string,
     *     requirements: list<array{id: string, title: string}>,
     *     interviewers: list<array{
     *         id: string,
     *         full_name: string,
     *         position: string|null,
     *         contacts: list<array{type: string, value: string}>|null,
     *         avatar_url: string|null
     *     }>,
     *     vacancy_sources: list<array{
     *         id: string,
     *         external_url: string,
     *         title: string,
     *         posted_at: string,
     *         contents: list<array{id: string, type: string, value: string}>
     *     }>,
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
        $preview = $vacancy->preview;

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
            'requirements' => $this->requirements($vacancy->requirements),
            'interviewers' => $this->interviewers($vacancy->interviewers),
            'vacancy_sources' => $this->sources($vacancy->sources),
            'closed_at' => $vacancy->closedAt?->format(DATE_ATOM),
            'created_at' => $vacancy->createdAt->format(DATE_ATOM),
            'updated_at' => $vacancy->updatedAt->format(DATE_ATOM),
            'version' => $vacancy->version,
        ];
    }

    /**
     * @param list<ContentDto> $contents
     * @return list<array{id: string, type: string, value: string}>
     */
    private function contents(array $contents): array
    {
        return array_map(
            static fn (ContentDto $content): array => [
                'id' => $content->id,
                'type' => $content->type,
                'value' => $content->value,
            ],
            $contents,
        );
    }

    /**
     * @param list<InterviewerSummaryDto> $interviewers
     * @return list<array{
     *     id: string,
     *     full_name: string,
     *     position: string|null,
     *     contacts: list<array{type: string, value: string}>|null,
     *     avatar_url: string|null
     * }>
     */
    private function interviewers(array $interviewers): array
    {
        return array_map(
            static fn (InterviewerSummaryDto $interviewer): array => [
                'id' => $interviewer->id,
                'full_name' => $interviewer->fullName,
                'position' => $interviewer->position,
                'contacts' => $interviewer->contacts,
                'avatar_url' => $interviewer->avatarUrl,
            ],
            $interviewers,
        );
    }

    /**
     * @param list<RequirementSummaryDto> $requirements
     * @return list<array{id: string, title: string}>
     */
    private function requirements(array $requirements): array
    {
        return array_map(
            static fn (RequirementSummaryDto $requirement): array => [
                'id' => $requirement->id,
                'title' => $requirement->title,
            ],
            $requirements,
        );
    }

    /**
     * @param list<SourceDto> $sources
     * @return list<array{
     *     id: string,
     *     external_url: string,
     *     title: string,
     *     posted_at: string,
     *     contents: list<array{id: string, type: string, value: string}>
     * }>
     */
    private function sources(array $sources): array
    {
        return array_map(
            fn (SourceDto $source): array => [
                'id' => $source->id,
                'external_url' => $source->externalUrl,
                'title' => $source->title,
                'posted_at' => $source->postedAt->format(DATE_ATOM),
                'contents' => $this->contents($source->contents),
            ],
            $sources,
        );
    }
}
