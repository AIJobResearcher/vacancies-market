<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Domain\Entities\Portal;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\PortalRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Infrastructure\Eloquents\Mappers\PortalMapper;
use App\Infrastructure\Eloquents\Repositories\PortalEloquentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PortalEloquentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PortalEloquentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PortalEloquentRepository(new PortalMapper());
    }

    public function testSavePersistsAndRestores(): void
    {
        $portal = Portal::create(PortalId::generate(), 'LinkedIn', 'https://linkedin.com');

        $this->repository->save($portal);

        $loaded = $this->repository->findById($portal->id());
        $this->assertNotNull($loaded);
        $this->assertEquals($portal->id()->value(), $loaded->id()->value());
        $this->assertSame('LinkedIn', $loaded->name());
        $this->assertSame('https://linkedin.com', $loaded->baseUrl());
        $this->assertSame(1, $loaded->version());
    }

    public function testSaveUpdatesExistingWithBumpedVersion(): void
    {
        $portal = Portal::create(PortalId::generate(), 'LinkedIn', 'https://linkedin.com');
        $this->repository->save($portal);

        $loaded = $this->repository->findById($portal->id());
        $this->assertNotNull($loaded);
        $loaded->updateConfig(crawlDelaySeconds: 10);
        $this->repository->save($loaded);

        $reloaded = $this->repository->findById($portal->id());
        $this->assertNotNull($reloaded);
        $this->assertSame(10, $reloaded->crawlDelaySeconds());
        $this->assertSame(2, $reloaded->version());
    }

    public function testSaveThrowsVersionConflictOnStaleUpdate(): void
    {
        $portal = Portal::create(PortalId::generate(), 'LinkedIn', 'https://linkedin.com');
        $this->repository->save($portal);

        $stale = $this->repository->findById($portal->id());
        $fresh = $this->repository->findById($portal->id());
        $this->assertNotNull($stale);
        $this->assertNotNull($fresh);

        $fresh->updateConfig(crawlDelaySeconds: 10);
        $this->repository->save($fresh);

        $stale->updateConfig(crawlDelaySeconds: 99);
        $this->expectException(VersionConflictException::class);
        $this->repository->save($stale);
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->repository->findById(PortalId::generate()));
    }

    public function testRepositoryImplementsDomainInterface(): void
    {
        $this->assertInstanceOf(PortalRepositoryInterface::class, $this->repository);
    }

    public function testRepositoryResolvesFromContainer(): void
    {
        $resolved = $this->app->make(PortalRepositoryInterface::class);
        $this->assertInstanceOf(PortalEloquentRepository::class, $resolved);
    }
}
