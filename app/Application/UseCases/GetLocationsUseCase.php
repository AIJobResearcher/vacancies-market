<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\DTOs\LocationDto;
use App\Domain\Entities\Location;
use App\Domain\Repositories\LocationRepositoryInterface;

final class GetLocationsUseCase
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private readonly LocationRepositoryInterface $locationRepository)
    {
    }

    /**
     * @return list<LocationDto>
     */
    public function handle(): array
    {
        return $this->buildTree($this->locationRepository->findAll(), null);
    }

    /**
     * @param list<Location> $locations
     * @return list<LocationDto>
     */
    private function buildTree(array $locations, ?int $parentId): array
    {
        $tree = [];

        foreach ($locations as $location) {
            if ($location->parentId() === $parentId) {
                $tree[] = $this->toNode($location, $locations);
            }
        }

        return $tree;
    }

    /**
     * @param list<Location> $locations
     */
    private function toNode(Location $location, array $locations): LocationDto
    {
        return new LocationDto(
            id: $location->id(),
            name: $location->name(),
            isoName: $location->isoName(),
            parentId: $location->parentId(),
            type: $location->type(),
            createdAt: $location->createdAt(),
            updatedAt: $location->updatedAt(),
            children: $this->buildTree($locations, $location->id()),
        );
    }
}
