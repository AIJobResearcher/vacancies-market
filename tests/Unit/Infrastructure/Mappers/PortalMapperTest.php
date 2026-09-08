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
        $this->assertSame('LinkedIn', $model->name);
        $this->assertSame('https://linkedin.com', $model->base_url);
        $this->assertSame('https://api.linkedin.com', $model->api_endpoint);
        $this->assertSame(5, $model->crawl_delay_seconds);
        $this->assertSame(2, $model->version);
    }

    public function testToDomainRestoresDomainFromModel(): void
    {
        $portal = (new PortalMapper())->toDomain($this->model());

        $this->assertSame('99999999-9999-9999-9999-999999999999', $portal->id()->value());
        $this->assertSame('LinkedIn', $portal->name());
        $this->assertSame('https://linkedin.com', $portal->baseUrl());
        $this->assertSame('https://api.linkedin.com', $portal->apiEndpoint());
        $this->assertSame(5, $portal->crawlDelaySeconds());
        $this->assertSame(2, $portal->version());
        $this->assertEquals(new DateTimeImmutable('2025-01-01 10:00:00'), $portal->createdAt());
    }

    private function domainPortal(): Portal
    {
        return Portal::reconstitute(
            PortalId::fromString('99999999-9999-9999-9999-999999999999'),
            'LinkedIn',
            'https://linkedin.com',
            'https://api.linkedin.com',
            5,
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-02-01 10:00:00'),
            2,
        );
    }

    private function model(): PortalModel
    {
        $model = new PortalModel();
        $model->id = '99999999-9999-9999-9999-999999999999';
        $model->name = 'LinkedIn';
        $model->base_url = 'https://linkedin.com';
        $model->api_endpoint = 'https://api.linkedin.com';
        $model->crawl_delay_seconds = 5;
        $model->created_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $model->updated_at = new DateTimeImmutable('2025-02-01 10:00:00');
        $model->version = 2;

        return $model;
    }
}
