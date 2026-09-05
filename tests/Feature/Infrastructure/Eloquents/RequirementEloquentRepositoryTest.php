<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Domain\Entities\Requirement;
use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Infrastructure\Eloquents\Mappers\RequirementMapper;
use App\Infrastructure\Eloquents\Repositories\RequirementEloquentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RequirementEloquentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private const EMPLOYER_ID = '11111111-1111-1111-1111-111111111111';

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

    public function testIsReferencedByActiveVacancyOrJob(): void
    {
        $requirement = Requirement::create(RequirementId::generate(), 'PHP');
        $this->repository->save($requirement);
        $this->assertFalse($this->repository->isReferencedByActiveVacancyOrJob($requirement->id()));

        $vacancyId = '66666666-6666-6666-6666-666666666666';
        $this->insertOpenVacancy($vacancyId);
        DB::table('vacancy_requirement_assignments')->insert([
            'id' => '33333333-3333-3333-3333-333333333333',
            'vacancy_id' => $vacancyId,
            'requirement_id' => $requirement->id()->value(),
            'assigned_at' => '2025-01-01 10:00:00',
            'version' => 1,
        ]);

        $this->assertTrue($this->repository->isReferencedByActiveVacancyOrJob($requirement->id()));
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

    private function insertOpenVacancy(string $id): void
    {
        DB::table('employers')->insert([
            'id' => self::EMPLOYER_ID,
            'title' => 'Acme',
            'version' => 1,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);

        DB::table('vacancies')->insert([
            'id' => $id,
            'employer_id' => self::EMPLOYER_ID,
            'title' => 'Role',
            'description' => null,
            'salary_min' => 0,
            'salary_max' => null,
            'salary_currency' => 'USD',
            'status' => 'open',
            'country' => null,
            'city' => null,
            'employment_type' => 'full-time',
            'workplace' => 'remote',
            'posted_at' => '2025-01-01 10:00:00',
            'version' => 1,
            'external_urls' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);
    }
}
