<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\InvalidUuidFormatException;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class EntityIdTest extends TestCase
{
    public static function validUuidProvider(): array
    {
        return [
            'valid uuid 1' => [Uuid::uuid4()->toString()],
            'valid uuid 2' => [Uuid::uuid4()->toString()],
        ];
    }

    public static function invalidUuidProvider(): array
    {
        return [
            'non-string' => ['not-a-uuid'],
            'empty' => [''],
            'short' => ['123'],
        ];
    }

    #[DataProvider('validUuidProvider')]
    public function test_from_string_accepts_valid_uuid(string $uuid): void
    {
        $id = VacancyId::fromString($uuid);
        $this->assertEquals($uuid, $id->value());
    }

    #[DataProvider('invalidUuidProvider')]
    public function test_from_string_throws_for_invalid_uuid(string $invalid): void
    {
        $this->expectException(InvalidUuidFormatException::class);
        VacancyId::fromString($invalid);
    }

    public function test_generate_returns_valid_uuid(): void
    {
        $id = VacancyId::generate();
        $this->assertTrue(Uuid::isValid($id->value()));
    }

    public function test_equals(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $id1 = VacancyId::fromString($uuid);
        $id2 = VacancyId::fromString($uuid);
        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals(VacancyId::generate()));
    }

    public function test_to_string_returns_value(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $id = VacancyId::fromString($uuid);
        $this->assertEquals($uuid, (string) $id);
    }
}
