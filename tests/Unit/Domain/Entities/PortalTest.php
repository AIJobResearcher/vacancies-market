<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Portal;
use App\Domain\Exceptions\ValidationException\PortalBaseUrlEmptyException;
use App\Domain\Exceptions\ValidationException\PortalCodeEmptyException;
use App\Domain\Exceptions\ValidationException\PortalNameEmptyException;
use App\Domain\ValueObjects\EntityIds\PortalId;
use DateTimeImmutable;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PortalTest extends TestCase
{
    private PortalId $portalId;

    #[Override]
    protected function setUp(): void
    {
        $this->portalId = PortalId::generate();
    }

    /**
     * @return array<string, array{0: string, 1: class-string<\Throwable>}>
     */
    public static function emptyCreateProvider(): array
    {
        return [
            'empty code' => ['code', PortalCodeEmptyException::class],
            'empty name' => ['name', PortalNameEmptyException::class],
            'empty base url' => ['baseUrl', PortalBaseUrlEmptyException::class],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: class-string<\Throwable>}>
     */
    public static function emptyUpdateProvider(): array
    {
        return [
            'empty code' => ['code', PortalCodeEmptyException::class],
            'empty name' => ['name', PortalNameEmptyException::class],
            'empty base url' => ['baseUrl', PortalBaseUrlEmptyException::class],
        ];
    }

    public function testCreateValid(): void
    {
        $portal = Portal::createPortal($this->portalId, 'linkedin', 'LinkedIn', 'https://linkedin.com');

        $this->assertSame($this->portalId, $portal->id());
        $this->assertSame('linkedin', $portal->code());
        $this->assertSame('LinkedIn', $portal->name());
        $this->assertSame('https://linkedin.com', $portal->baseUrl());
        $this->assertSame($portal->createdAt(), $portal->updatedAt());
    }

    public function testCreateWithoutBaseUrlIsAllowed(): void
    {
        $portal = Portal::createPortal($this->portalId, 'linkedin', 'LinkedIn');

        $this->assertNull($portal->baseUrl());
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('emptyCreateProvider')]
    public function testCreateWithEmptyValueThrows(string $field, string $exceptionClass): void
    {
        $this->expectException($exceptionClass);

        match ($field) {
            'code' => Portal::createPortal($this->portalId, '', 'LinkedIn', 'https://linkedin.com'),
            'name' => Portal::createPortal($this->portalId, 'linkedin', '', 'https://linkedin.com'),
            'baseUrl' => Portal::createPortal($this->portalId, 'linkedin', 'LinkedIn', ''),
            default => throw new InvalidArgumentException(sprintf('Unknown field: %s', $field)),
        };
    }

    public function testUpdatePortalChangesProvidedFieldsOnly(): void
    {
        $portal = Portal::createPortal($this->portalId, 'linkedin', 'LinkedIn', 'https://linkedin.com');

        $portal->updatePortal(code: 'djinni');

        $this->assertSame('djinni', $portal->code());
        $this->assertSame('LinkedIn', $portal->name());
        $this->assertSame('https://linkedin.com', $portal->baseUrl());
    }

    public function testUpdatePortalKeepsAllFieldsWhenOmitted(): void
    {
        $portal = Portal::createPortal($this->portalId, 'linkedin', 'LinkedIn', 'https://linkedin.com');

        $portal->updatePortal();

        $this->assertSame('linkedin', $portal->code());
        $this->assertSame('LinkedIn', $portal->name());
        $this->assertSame('https://linkedin.com', $portal->baseUrl());
    }

    public function testUpdatePortalMovesUpdatedAt(): void
    {
        $portal = Portal::createPortal($this->portalId, 'linkedin', 'LinkedIn');
        $updatedAt = $portal->updatedAt();

        $portal->updatePortal(name: 'LinkedIn Jobs');

        $this->assertNotSame($updatedAt, $portal->updatedAt());
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('emptyUpdateProvider')]
    public function testUpdatePortalWithEmptyValueThrows(string $field, string $exceptionClass): void
    {
        $portal = $this->portal();

        $this->expectException($exceptionClass);

        match ($field) {
            'code' => $portal->updatePortal(code: ''),
            'name' => $portal->updatePortal(name: ''),
            'baseUrl' => $portal->updatePortal(baseUrl: ''),
            default => throw new InvalidArgumentException(sprintf('Unknown field: %s', $field)),
        };
    }

    public function testReconstituteRestoresState(): void
    {
        $createdAt = new DateTimeImmutable('2025-01-01T00:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2025-01-02T00:00:00+00:00');

        $portal = Portal::reconstitute(
            $this->portalId,
            'linkedin',
            'LinkedIn',
            'https://linkedin.com',
            $createdAt,
            $updatedAt
        );

        $this->assertSame($this->portalId, $portal->id());
        $this->assertSame('linkedin', $portal->code());
        $this->assertSame('LinkedIn', $portal->name());
        $this->assertSame('https://linkedin.com', $portal->baseUrl());
        $this->assertSame($createdAt, $portal->createdAt());
        $this->assertSame($updatedAt, $portal->updatedAt());
    }

    public function testReconstituteSkipsValidation(): void
    {
        $createdAt = new DateTimeImmutable('2025-01-01T00:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2025-01-02T00:00:00+00:00');

        $portal = Portal::reconstitute($this->portalId, '', '', '', $createdAt, $updatedAt);

        $this->assertSame('', $portal->code());
        $this->assertSame('', $portal->name());
        $this->assertSame('', $portal->baseUrl());
    }

    private function portal(): Portal
    {
        return Portal::createPortal($this->portalId, 'linkedin', 'LinkedIn', 'https://linkedin.com');
    }
}
