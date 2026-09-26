<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Domain\Entities\Requirement;
use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Infrastructure\Eloquents\Mappers\RequirementMapper;
use App\Infrastructure\Eloquents\Repositories\RequirementEloquentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RequirementEloquentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private RequirementEloquentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RequirementEloquentRepository(new RequirementMapper());
    }

    public function testSavePersistsAndRestores(): void
    {
        $requirement = Requirement::create(RequirementId::generate(), 'PHP', 'PHP 8.5', 'technical');
        $this->repository->save($requirement);

        $loaded = $this->repository->findById($requirement->id());
        $this->assertNotNull($loaded);
        $this->assertEquals($requirement->id()->value(), $loaded->id()->value());
        $this->assertSame('PHP', $loaded->title());
    }

    public function testSaveUpdatesExisting(): void
    {
        $requirement = Requirement::create(RequirementId::generate(), 'PHP');
        $this->repository->save($requirement);

        $loaded = $this->repository->findById($requirement->id());
        $this->assertNotNull($loaded);
        $loaded->update(description: 'PHP 8.5');
        $this->repository->save($loaded);

        $reloaded = $this->repository->findById($requirement->id());
        $this->assertNotNull($reloaded);
        $this->assertSame('PHP 8.5', $reloaded->description());
    }

    public function testFindByTitleCaseInsensitive(): void
    {
        $requirement = Requirement::create(RequirementId::generate(), 'PHP');
        $this->repository->save($requirement);

        $found = $this->repository->findByTitleCaseInsensitive('php');
        $this->assertNotNull($found);
        $this->assertEquals($requirement->id()->value(), $found->id()->value());
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->repository->findById(RequirementId::generate()));
    }

    public function testRepositoryImplementsDomainInterface(): void
    {
        $this->assertInstanceOf(RequirementRepositoryInterface::class, $this->repository);
    }

    public function testRepositoryResolvesFromContainer(): void
    {
        $resolved = $this->app->make(RequirementRepositoryInterface::class);
        $this->assertInstanceOf(RequirementEloquentRepository::class, $resolved);
    }
}
