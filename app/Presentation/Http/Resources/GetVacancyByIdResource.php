<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

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
        /**
         * @var array{
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
         * } $vacancy
         */
        $vacancy = $this->resource;

        return [
            'id' => $vacancy['id'],
            'title' => $vacancy['title'],
            'employer_id' => $vacancy['employer_id'],
            'employer_title' => $vacancy['employer_title'],
            'min_salary' => $vacancy['min_salary'],
            'max_salary' => $vacancy['max_salary'],
            'country' => $vacancy['country'],
            'city' => $vacancy['city'],
            'employment_type' => $vacancy['employment_type'],
            'workplace' => $vacancy['workplace'],
            'status' => $vacancy['status'],
            'posted_at' => $vacancy['posted_at'],
            'description' => $vacancy['description'],
            'requirements' => $vacancy['requirements'],
            'internal_url' => $vacancy['internal_url'],
            'external_urls' => $vacancy['external_urls'],
            'employer' => $vacancy['employer'],
            'interviewer' => $vacancy['interviewer'],
            'closed_at' => $vacancy['closed_at'],
            'created_at' => $vacancy['created_at'],
            'updated_at' => $vacancy['updated_at'],
            'version' => $vacancy['version'],
        ];
    }
}
