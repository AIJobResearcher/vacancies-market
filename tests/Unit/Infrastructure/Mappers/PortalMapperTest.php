<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Mappers;

use App\Domain\Entities\Portal;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Infrastructure\Eloquents\Mappers\PortalMapper;
use App\Infrastructure\Eloquents\Models\PortalModel;
use DateTimeImmutable;
use Tests\TestCase;

final class PortalMapperTest extends TestCase
{
    public function testToEloquentMapsDomainToModel(): void
    {
        $portal = $this->domainPortal();

        $model = (new PortalMapper())->toEloquent($portal);

        $this->assertSame($portal->id()->value(), $model->id);
        $this->assertSame('linkedin', $model->code);
        $this->assertSame('LinkedIn', $model->name);
        $this->assertSame('https://linkedin.com', $model->base_url);
    }

    public function testToDomainRestoresDomainFromModel(): void
    {
        $portal = (new PortalMapper())->toDomain($this->model());

        $this->assertSame('99999999-9999-9999-9999-999999999999', $portal->id()->value());
        $this->assertSame('linkedin', $portal->code());
        $this->assertSame('LinkedIn', $portal->name());
        $this->assertSame('https://linkedin.com', $portal->baseUrl());
        $this->assertEquals(new DateTimeImmutable('2025-01-01 10:00:00'), $portal->createdAt());
        $this->assertEquals(new DateTimeImmutable('2025-02-01 10:00:00'), $portal->updatedAt());
    }

    private function domainPortal(): Portal
    {
        return Portal::reconstitute(
            PortalId::fromString('99999999-9999-9999-9999-999999999999'),
            'linkedin',
            'LinkedIn',
            'https://linkedin.com',
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-02-01 10:00:00'),
        );
    }

    private function model(): PortalModel
    {
        $model = new PortalModel();
        $model->id = '99999999-9999-9999-9999-999999999999';
        $model->code = 'linkedin';
        $model->name = 'LinkedIn';
        $model->base_url = 'https://linkedin.com';
        $model->created_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $model->updated_at = new DateTimeImmutable('2025-02-01 10:00:00');

        return $model;
    }
}
