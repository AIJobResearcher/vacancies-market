<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\VacancySource;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\EntityIds\VacancySourceId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VacancySourceTest extends TestCase
{
    public function testConstructAndGetters(): void
    {
        $id = VacancySourceId::generate();
        $vacancyId = VacancyId::generate();
        $firstSeen = new DateTimeImmutable('2025-01-01T00:00:00+00:00');
        $lastSeen = new DateTimeImmutable('2025-01-02T00:00:00+00:00');

        $source = new VacancySource(
            $id,
            $vacancyId,
            'linkedin',
            'ext-123',
            'https://linkedin.com/123',
            $firstSeen,
            $lastSeen
        );

        $this->assertSame('linkedin', $source->sourceKey());
        $this->assertSame('ext-123', $source->externalVacancyId());
        $this->assertSame('https://linkedin.com/123', $source->externalUrl());
        $this->assertSame($lastSeen, $source->lastSeenAt());
        $this->assertFalse($source->isPrimary());
    }

    public function testConstructMarksPrimary(): void
    {
        $source = new VacancySource(
            VacancySourceId::generate(),
            VacancyId::generate(),
            'linkedin',
            'ext-123',
            'https://linkedin.com/123',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
            true
        );

        $this->assertTrue($source->isPrimary());
    }

    public function testUpdateLastSeenAt(): void
    {
        $source = new VacancySource(
            VacancySourceId::generate(),
            VacancyId::generate(),
            'linkedin',
            'ext-123',
            'https://linkedin.com/123',
            new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2025-01-01T00:00:00+00:00')
        );
        $newLastSeen = new DateTimeImmutable('2025-01-10T00:00:00+00:00');

        $source->updateLastSeenAt($newLastSeen);

        $this->assertSame($newLastSeen, $source->lastSeenAt());
    }
}
