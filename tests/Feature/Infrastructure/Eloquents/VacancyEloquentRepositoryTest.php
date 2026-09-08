<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Domain\Entities\Vacancy;
use App\Domain\Entities\VacancySource;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\EntityIds\VacancySourceId;
use App\Domain\ValueObjects\ExternalUrls;
use App\Domain\ValueObjects\Salary;
use App\Infrastructure\Eloquents\Mappers\VacancyMapper;
use App\Infrastructure\Eloquents\Repositories\VacancyEloquentRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VacancyEloquentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private const EMPLOYER_ID = '11111111-1111-1111-1111-111111111111';

    private VacancyEloquentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new VacancyEloquentRepository(new VacancyMapper());
    }

    public function testSavePersistsAndRestoresAggregateWithChildren(): void
    {
        $this->insertEmployer();
        $vacancy = $this->newVacancy();
        $vacancy->addRequirement(RequirementId::generate());
        $vacancy->assignToJob(JobId::generate(), 85);
        $vacancy->addSource($this->source($vacancy));

        $this->repository->save($vacancy);

        $loaded = $this->repository->findById($vacancy->id());
        $this->assertNotNull($loaded);
        $this->assertEquals($vacancy->id()->value(), $loaded->id()->value());
        $this->assertEquals('Remote Engineer', $loaded->title());
        $this->assertCount(1, $loaded->requirementAssignments());
        $this->assertCount(1, $loaded->jobAssignments());
        $this->assertCount(1, $loaded->sources());

        $this->assertDatabaseHas('outbox_messages', ['event_type' => 'VacancyImported']);
    }

    public function testSaveUpdatesExistingAndEmitsCloseEvent(): void
    {
        $this->insertEmployer();
        $vacancy = $this->newVacancy();
        $this->repository->save($vacancy);

        $loaded = $this->repository->findById($vacancy->id());
        $this->assertNotNull($loaded);
        $loaded->close();
        $this->repository->save($loaded);

        $reloaded = $this->repository->findById($vacancy->id());
        $this->assertNotNull($reloaded);
        $this->assertSame(VacancyStatusEnum::CLOSED->value, $reloaded->status());
        $this->assertNotNull($reloaded->closedAt());
        $this->assertDatabaseHas('outbox_messages', ['event_type' => 'VacancyClosed']);
    }

    public function testSaveThrowsVersionConflictOnStaleUpdate(): void
    {
        $this->insertEmployer();
        $vacancy = $this->newVacancy();
        $this->repository->save($vacancy);

        $stale = $this->repository->findById($vacancy->id());
        $this->assertNotNull($stale);
        $fresh = $this->repository->findById($vacancy->id());
        $this->assertNotNull($fresh);

        $fresh->close();
        $this->repository->save($fresh);

        $stale->close();
        $this->expectException(VersionConflictException::class);
        $this->repository->save($stale);
    }

    public function testFindActiveByJobIdReturnsOnlyOpenAssignments(): void
    {
        $this->insertEmployer();
        $jobId = JobId::generate();

        $open = $this->newVacancy();
        $open->assignToJob($jobId, 90);
        $this->repository->save($open);

        $closed = $this->newVacancy('Closed Role');
        $closed->assignToJob($jobId, 70);
        $closed->close();
        $this->repository->save($closed);

        $found = $this->repository->findActiveByJobId($jobId);
        $this->assertCount(1, $found);
        $this->assertEquals($open->id()->value(), $found[0]->id()->value());
    }

    public function testReconcileRemovesDeletedRequirementAssignment(): void
    {
        $this->insertEmployer();
        $vacancy = $this->newVacancy();
        $reqId = RequirementId::generate();
        $vacancy->addRequirement($reqId);
        $this->repository->save($vacancy);

        $loaded = $this->repository->findById($vacancy->id());
        $this->assertNotNull($loaded);
        $loaded->removeRequirement($reqId);
        $this->repository->save($loaded);

        $reloaded = $this->repository->findById($vacancy->id());
        $this->assertNotNull($reloaded);
        $this->assertCount(0, $reloaded->requirementAssignments());
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->repository->findById(VacancyId::generate()));
    }

    public function testRepositoryImplementsDomainInterface(): void
    {
        $this->assertInstanceOf(VacancyRepositoryInterface::class, $this->repository);
    }

    public function testRepositoryResolvesFromContainer(): void
    {
        $resolved = $this->app->make(VacancyRepositoryInterface::class);
        $this->assertInstanceOf(VacancyEloquentRepository::class, $resolved);
        $this->assertSame($resolved, $this->app->make(VacancyRepositoryInterface::class));
    }

    private function newVacancy(string $title = 'Remote Engineer'): Vacancy
    {
        return Vacancy::create(
            VacancyId::generate(),
            EmployerId::fromString(self::EMPLOYER_ID),
            $title,
            'Description',
            new Salary(1000, 2000, 'USD'),
            'USA',
            'NYC',
            EmploymentTypeEnum::FULL_TIME,
            WorkplaceEnum::REMOTE,
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new ExternalUrls(['https://example.com/vacancy']),
            null,
            'corr-id'
        );
    }

    private function source(Vacancy $vacancy): VacancySource
    {
        return new VacancySource(
            VacancySourceId::generate(),
            $vacancy->id(),
            'linkedin',
            'ext123',
            'https://linkedin.com/123',
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-01-02 10:00:00'),
            null,
            true
        );
    }

    private function insertEmployer(): void
    {
        DB::table('employers')->insert([
            'id' => self::EMPLOYER_ID,
            'title' => 'Acme',
            'version' => 1,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);
    }
}
