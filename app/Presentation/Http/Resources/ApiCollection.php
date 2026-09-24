<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class ApiCollection extends ResourceCollection
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     * @param array<string, mixed> $paginated
     * @param array{links: array<string, string|null>, meta: array<string, mixed>} $default
     * @return array{meta: array{current_page: int, per_page: int, total: int, last_page: int}}
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'current_page' => (int) $default['meta']['current_page'],
                'per_page' => (int) $default['meta']['per_page'],
                'total' => (int) $default['meta']['total'],
                'last_page' => (int) $default['meta']['last_page'],
            ],
        ];
    }
}
