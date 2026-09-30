<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Domain\DTOs\LocationDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

final class GetLocationsResource extends JsonResource
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     * @return array{
     *     id: int,
     *     name: string,
     *     iso_name: string|null,
     *     parent_id: int|null,
     *     type: string,
     *     created_at: string,
     *     updated_at: string,
     *     children: AnonymousResourceCollection
     * }
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var LocationDto $location */
        $location = $this->resource;

        return [
            'id' => $location->id,
            'name' => $location->name,
            'iso_name' => $location->isoName,
            'parent_id' => $location->parentId,
            'type' => $location->type->value,
            'created_at' => $location->createdAt->format(DATE_ATOM),
            'updated_at' => $location->updatedAt->format(DATE_ATOM),
            'children' => self::collection($location->children),
        ];
    }
}
