<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

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
         * } $preview
         */
        $preview = $this->resource;

        return [
            'id' => $preview['id'],
            'title' => $preview['title'],
            'employer_id' => $preview['employer_id'],
            'employer_title' => $preview['employer_title'],
            'min_salary' => $preview['min_salary'],
            'max_salary' => $preview['max_salary'],
            'country' => $preview['country'],
            'city' => $preview['city'],
            'employment_type' => $preview['employment_type'],
            'workplace' => $preview['workplace'],
            'status' => $preview['status'],
            'posted_at' => $preview['posted_at'],
        ];
    }
}
