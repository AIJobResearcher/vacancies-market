<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Domain\Entities\Job;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\JobRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Infrastructure\Eloquents\Mappers\JobMapper;
use App\Infrastructure\Eloquents\Repositories\JobEloquentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class JobEloquentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private JobEloquentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new JobEloquentRepository(new JobMapper());
    }

    public function testSavePersistsAndRestoresWithRequirements(): void
    {
        $requirementId = RequirementId::generate();
        $this->insertRequirement($requirementId->value(), 'PHP');

        $job = Job::create(JobId::generate(), 'Software Engineer', 'technical');
        $job->addRequirement($requirementId);
        $this->repository->save($job);

        $loaded = $this->repository->findById($job->id());
        $this->assertNotNull($loaded);
        $this->assertEquals($job->id()->value(), $loaded->id()->value());
        $this->assertSame('Software Engineer', $loaded->title());
        $this->assertCount(1, $loaded->requirementIds());
        $this->assertEquals($requirementId->value(), $loaded->requirementIds()[0]->value());
    }

    public function testReconcileRemovesDeletedRequirement(): void
    {
        $requirementId = RequirementId::generate();
        $this->insertRequirement($requirementId->value(), 'PHP');

        $job = Job::create(JobId::generate(), 'Software Engineer', 'IT');
        $job->addRequirement($requirementId);
        $this->repository->save($job);

        $loaded = $this->repository->findById($job->id());
        $this->assertNotNull($loaded);
        $loaded->removeRequirement($requirementId);
        $this->repository->save($loaded);

        $reloaded = $this->repository->findById($job->id());
        $this->assertNotNull($reloaded);
        $this->assertCount(0, $reloaded->requirementIds());
    }

    public function testSaveThrowsVersionConflictOnStaleUpdate(): void
    {
        $job = Job::create(JobId::generate(), 'Software Engineer', 'IT');
        $this->repository->save($job);

        $stale = $this->repository->findById($job->id());
        $fresh = $this->repository->findById($job->id());
        $this->assertNotNull($stale);
        $this->assertNotNull($fresh);

        $newRequirementId = RequirementId::generate();
        $this->insertRequirement($newRequirementId->value(), 'SQL');
        $fresh->addRequirement($newRequirementId);
        $this->repository->save($fresh);

        $stale->softDelete();
        $this->expectException(VersionConflictException::class);
        $this->repository->save($stale);
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->repository->findById(JobId::generate()));
    }

    public function testRepositoryImplementsDomainInterface(): void
    {
        $this->assertInstanceOf(JobRepositoryInterface::class, $this->repository);
    }

    public function testRepositoryResolvesFromContainer(): void
    {
        $resolved = $this->app->make(JobRepositoryInterface::class);
        $this->assertInstanceOf(JobEloquentRepository::class, $resolved);
    }

    private function insertRequirement(string $id, string $title): void
    {
        DB::table('requirements')->insert([
            'id' => $id,
            'title' => $title,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);
    }
}
