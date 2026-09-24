<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

final class GetJobsByIdsResource extends JsonResource
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     * @return array{
     *     id: string,
     *     title: string,
     *     category: string,
     *     sub_category: string|null,
     *     parent_job_id: string|null,
     *     parent_job_title: string|null,
     * }
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /**
         * @var array{
         *     id: string,
         *     title: string,
         *     category: string,
         *     sub_category: string|null,
         *     parent_job_id: string|null,
         *     parent_job_title: string|null,
         * } $job
         */
        $job = $this->resource;

        return [
            'id' => $job['id'],
            'title' => $job['title'],
            'category' => $job['category'],
            'sub_category' => $job['sub_category'],
            'parent_job_id' => $job['parent_job_id'],
            'parent_job_title' => $job['parent_job_title'],
        ];
    }
}
