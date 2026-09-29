<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Location;
use App\Domain\Enums\LocationTypeEnum;
use App\Domain\Exceptions\ValidationException\LocationNameEmptyException;
use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    private int $locationId;

    #[Override]
    protected function setUp(): void
    {
        $this->locationId = 804;
    }

    /**
     * @return array<string, array{0: string, 1: LocationTypeEnum}>
     */
    public static function validCreateProvider(): array
    {
        return [
            'city' => ['Kyiv', LocationTypeEnum::CITY],
            'country' => ['Ukraine', LocationTypeEnum::COUNTRY],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function emptyNameProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
        ];
    }

    #[DataProvider('validCreateProvider')]
    public function testCreateValid(string $name, LocationTypeEnum $type): void
    {
        $location = Location::createLocation($this->locationId, $name, $type);

        $this->assertSame($this->locationId, $location->id());
        $this->assertSame($name, $location->name());
        $this->assertSame($type, $location->type());
        $this->assertNull($location->isoName());
        $this->assertNull($location->parentId());
        $this->assertSame($location->createdAt(), $location->updatedAt());
    }

    public function testCreateWithOptionalFieldsTrimsName(): void
    {
        $location = Location::createLocation($this->locationId, '  Kyiv  ', LocationTypeEnum::CITY, 'UA-30', 703448);

        $this->assertSame('Kyiv', $location->name());
        $this->assertSame('UA-30', $location->isoName());
        $this->assertSame(703448, $location->parentId());
    }

    #[DataProvider('emptyNameProvider')]
    public function testCreateWithEmptyNameThrows(string $name): void
    {
        $this->expectException(LocationNameEmptyException::class);
        Location::createLocation($this->locationId, $name, LocationTypeEnum::CITY);
    }

    public function testUpdateLocationChangesAllProvidedFields(): void
    {
        $location = $this->location();

        $location->updateLocation('Lviv', 'UA-46', 703448, LocationTypeEnum::REGION);

        $this->assertSame('Lviv', $location->name());
        $this->assertSame('UA-46', $location->isoName());
        $this->assertSame(703448, $location->parentId());
        $this->assertSame(LocationTypeEnum::REGION, $location->type());
    }

    public function testUpdateLocationKeepsOmittedFields(): void
    {
        $location = $this->location();

        $location->updateLocation(name: 'Lviv');

        $this->assertSame('Lviv', $location->name());
        $this->assertSame('UA-30', $location->isoName());
        $this->assertSame(703448, $location->parentId());
        $this->assertSame(LocationTypeEnum::CITY, $location->type());
    }

    public function testUpdateLocationTrimsName(): void
    {
        $location = $this->location();

        $location->updateLocation('  Lviv  ');

        $this->assertSame('Lviv', $location->name());
    }

    public function testUpdateLocationMovesUpdatedAt(): void
    {
        $location = $this->location();
        $updatedAt = $location->updatedAt();

        $location->updateLocation(name: 'Lviv');

        $this->assertNotSame($updatedAt, $location->updatedAt());
    }

    #[DataProvider('emptyNameProvider')]
    public function testUpdateLocationWithEmptyNameThrows(string $name): void
    {
        $location = $this->location();

        $this->expectException(LocationNameEmptyException::class);
        $location->updateLocation($name);
    }

    public function testReconstituteRestoresState(): void
    {
        $createdAt = new DateTimeImmutable('2025-01-01T00:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2025-01-02T00:00:00+00:00');

        $location = Location::reconstitute(
            $this->locationId,
            'Kyiv',
            'UA-30',
            703448,
            LocationTypeEnum::CITY,
            $createdAt,
            $updatedAt
        );

        $this->assertSame($this->locationId, $location->id());
        $this->assertSame('Kyiv', $location->name());
        $this->assertSame('UA-30', $location->isoName());
        $this->assertSame(703448, $location->parentId());
        $this->assertSame(LocationTypeEnum::CITY, $location->type());
        $this->assertSame($createdAt, $location->createdAt());
        $this->assertSame($updatedAt, $location->updatedAt());
    }

    private function location(): Location
    {
        return Location::createLocation($this->locationId, 'Kyiv', LocationTypeEnum::CITY, 'UA-30', 703448);
    }
}
