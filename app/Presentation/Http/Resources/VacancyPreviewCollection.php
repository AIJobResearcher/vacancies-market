<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class VacancyPreviewCollection extends ResourceCollection
{
    public $collects = VacancyPreviewResource::class;

    /**
     * @param  array<string, mixed>  $paginated
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public function paginationInformation($request, $paginated, $default): array
    {
        return [
            'links' => [
                'first' => $paginated['first_page_url'] ?? null,
                'last' => $paginated['last_page_url'] ?? null,
                'prev' => $paginated['prev_page_url'] ?? null,
                'next' => $paginated['next_page_url'] ?? null,
            ],
            'meta' => [
                'current_page' => $paginated['current_page'] ?? null,
                'per_page' => $paginated['per_page'] ?? null,
                'total' => $paginated['total'] ?? null,
                'last_page' => $paginated['last_page'] ?? null,
            ],
        ];
    }

    public function toResponse($request): Response
    {
        $response = parent::toResponse($request);

        if ($this->resource instanceof LengthAwarePaginator) {
            $response->headers->set('X-Total-Count', (string) $this->resource->total());
        }

        return $response;
    }
}
