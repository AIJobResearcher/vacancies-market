<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Domain\Entities\Employer;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\EmployerRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Infrastructure\Eloquents\Mappers\EmployerMapper;
use App\Infrastructure\Eloquents\Repositories\EmployerEloquentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EmployerEloquentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EmployerEloquentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EmployerEloquentRepository(new EmployerMapper());
    }

    public function testSavePersistsRestoresAndEmitsImportedEvent(): void
    {
        $employer = Employer::create(EmployerId::generate(), 'Acme', 'Description');

        $this->repository->save($employer);

        $loaded = $this->repository->findById($employer->id());
        $this->assertNotNull($loaded);
        $this->assertEquals($employer->id()->value(), $loaded->id()->value());
        $this->assertSame('Acme', $loaded->title());
        $this->assertSame(1, $loaded->version());

        $this->assertDatabaseHas('outbox_messages', ['event_type' => 'EmployerImported']);
    }

    public function testSaveUpdatesExistingWithBumpedVersion(): void
    {
        $employer = Employer::create(EmployerId::generate(), 'Acme');
        $this->repository->save($employer);

        $loaded = $this->repository->findById($employer->id());
        $this->assertNotNull($loaded);
        $loaded->updateDetails(title: 'Acme Inc');
        $this->repository->save($loaded);

        $reloaded = $this->repository->findById($employer->id());
        $this->assertNotNull($reloaded);
        $this->assertSame('Acme Inc', $reloaded->title());
        $this->assertSame(2, $reloaded->version());
    }

    public function testSaveThrowsVersionConflictOnStaleUpdate(): void
    {
        $employer = Employer::create(EmployerId::generate(), 'Acme');
        $this->repository->save($employer);

        $stale = $this->repository->findById($employer->id());
        $fresh = $this->repository->findById($employer->id());
        $this->assertNotNull($stale);
        $this->assertNotNull($fresh);

        $fresh->updateDetails(title: 'Fresh');
        $this->repository->save($fresh);

        $stale->updateDetails(title: 'Stale');
        $this->expectException(VersionConflictException::class);
        $this->repository->save($stale);
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->repository->findById(EmployerId::generate()));
    }

    public function testRepositoryImplementsDomainInterface(): void
    {
        $this->assertInstanceOf(EmployerRepositoryInterface::class, $this->repository);
    }

    public function testRepositoryResolvesFromContainer(): void
    {
        $resolved = $this->app->make(EmployerRepositoryInterface::class);
        $this->assertInstanceOf(EmployerEloquentRepository::class, $resolved);
    }
}
