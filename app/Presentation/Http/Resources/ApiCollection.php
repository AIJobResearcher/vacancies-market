<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class ApiCollection extends ResourceCollection
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedParam
     * @param array{current_page: int, per_page: int, total: int, last_page: int, ...} $paginated
     * @param array{
     *     links: array<string, string|null>,
     *     meta: array{current_page: int, per_page: int, total: int, last_page: int, ...}
     * } $default
     * @return array{meta: array{current_page: int, per_page: int, total: int, last_page: int}}
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'current_page' => $default['meta']['current_page'],
                'per_page' => $default['meta']['per_page'],
                'total' => $default['meta']['total'],
                'last_page' => $default['meta']['last_page'],
            ],
        ];
    }
}
