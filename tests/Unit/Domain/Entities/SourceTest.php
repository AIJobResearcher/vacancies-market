<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Source;
use App\Domain\Exceptions\ValidationException\ExternalUrlInvalidException;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Domain\ValueObjects\EntityIds\SourceId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SourceTest extends TestCase
{
    public function testConstructAndGetters(): void
    {
        $id = SourceId::generate();
        $vacancyId = VacancyId::generate();
        $portalId = PortalId::generate();
        $postedAt = new DateTimeImmutable('2025-01-01T00:00:00+00:00');
        $createdAt = new DateTimeImmutable('2025-01-02T00:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2025-01-03T00:00:00+00:00');

        $source = new Source(
            $id,
            $vacancyId,
            $portalId,
            'ext-123',
            'https://linkedin.com/123',
            'Senior PHP',
            $postedAt,
            $createdAt,
            $updatedAt
        );

        $this->assertSame($id, $source->id());
        $this->assertSame($vacancyId, $source->vacancyId());
        $this->assertSame($portalId, $source->portalId());
        $this->assertSame('ext-123', $source->externalVacancyId());
        $this->assertSame('https://linkedin.com/123', $source->externalUrl());
        $this->assertSame('Senior PHP', $source->title());
        $this->assertSame($postedAt, $source->postedAt());
        $this->assertSame($createdAt, $source->createdAt());
        $this->assertSame($updatedAt, $source->updatedAt());
        $this->assertSame([], $source->contents());
    }

    public function testConstructWithEmptyExternalUrlThrows(): void
    {
        $this->expectException(ExternalUrlInvalidException::class);

        new Source(
            SourceId::generate(),
            VacancyId::generate(),
            PortalId::generate(),
            null,
            '   ',
            'Senior PHP',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public function testRefreshUpdatesPortalTitleAndPostedAt(): void
    {
        $source = $this->source();
        $updatedAt = $source->updatedAt();
        $newPortalId = PortalId::generate();
        $newPostedAt = new DateTimeImmutable('2025-02-01T00:00:00+00:00');

        $source->refresh($newPortalId, 'Updated title', $newPostedAt);

        $this->assertSame($newPortalId, $source->portalId());
        $this->assertSame('Updated title', $source->title());
        $this->assertSame($newPostedAt, $source->postedAt());
        $this->assertNotSame($updatedAt, $source->updatedAt());
    }

    private function source(): Source
    {
        return new Source(
            SourceId::generate(),
            VacancyId::generate(),
            PortalId::generate(),
            'ext-123',
            'https://linkedin.com/123',
            'Senior PHP',
            new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2025-01-02T00:00:00+00:00'),
            new DateTimeImmutable('2025-01-03T00:00:00+00:00')
        );
    }
}
