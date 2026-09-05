<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Domain\Entities\Interviewer;
use App\Domain\Entities\InterviewerVacancyAssignment;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\InterviewerRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\EntityIds\InterviewerVacancyAssignmentId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Infrastructure\Eloquents\Mappers\InterviewerMapper;
use App\Infrastructure\Eloquents\Repositories\InterviewerEloquentRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InterviewerEloquentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private const EMPLOYER_ID = '11111111-1111-1111-1111-111111111111';
    private const INTERVIEWER_ID = '99999999-9999-9999-9999-999999999999';
    private const VACANCY_ID = '66666666-6666-6666-6666-666666666666';

    private InterviewerEloquentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new InterviewerEloquentRepository(new InterviewerMapper());
        $this->insertEmployer();
        $this->insertVacancy();
    }

    public function testSavePersistsAndRestoresActiveAssignment(): void
    {
        $interviewer = $this->interviewerWithActiveAssignment();

        $this->repository->save($interviewer);

        $loaded = $this->repository->findById($interviewer->id());
        $this->assertNotNull($loaded);
        $this->assertSame('Alice Smith', $loaded->fullName());
        $this->assertCount(1, $loaded->getVacancyAssignments());

        $assignment = $loaded->getVacancyAssignments()[0];
        $this->assertTrue($assignment->isActive());
        $this->assertEquals(self::VACANCY_ID, $assignment->vacancyId()->value());
    }

    public function testSavePersistsAndRestoresDeactivatedAssignment(): void
    {
        $interviewer = $this->interviewerWithInactiveAssignment();

        $this->repository->save($interviewer);

        $loaded = $this->repository->findById($interviewer->id());
        $this->assertNotNull($loaded);
        $assignment = $loaded->getVacancyAssignments()[0];
        $this->assertFalse($assignment->isActive());
        $this->assertNotNull($assignment->unassignedAt());
        $this->assertSame(2, $assignment->version());
    }

    public function testSaveThrowsVersionConflictOnStaleUpdate(): void
    {
        $interviewer = $this->interviewerWithNoAssignment();
        $this->repository->save($interviewer);

        $stale = $this->repository->findById($interviewer->id());
        $fresh = $this->repository->findById($interviewer->id());
        $this->assertNotNull($stale);
        $this->assertNotNull($fresh);

        $fresh->updateProfile(fullName: 'Alice Updated');
        $this->repository->save($fresh);

        $stale->updateProfile(fullName: 'Alice Stale');
        $this->expectException(VersionConflictException::class);
        $this->repository->save($stale);
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->repository->findById(InterviewerId::generate()));
    }

    public function testRepositoryImplementsDomainInterface(): void
    {
        $this->assertInstanceOf(InterviewerRepositoryInterface::class, $this->repository);
    }

    public function testRepositoryResolvesFromContainer(): void
    {
        $resolved = $this->app->make(InterviewerRepositoryInterface::class);
        $this->assertInstanceOf(InterviewerEloquentRepository::class, $resolved);
    }

    private function interviewerWithNoAssignment(): Interviewer
    {
        return $this->reconstitute([]);
    }

    private function interviewerWithActiveAssignment(): Interviewer
    {
        return $this->reconstitute([
            new InterviewerVacancyAssignment(
                InterviewerVacancyAssignmentId::generate(),
                InterviewerId::fromString(self::INTERVIEWER_ID),
                VacancyId::fromString(self::VACANCY_ID),
                new DateTimeImmutable('2025-01-05 10:00:00'),
                null,
                1,
            ),
        ]);
    }

    private function interviewerWithInactiveAssignment(): Interviewer
    {
        return $this->reconstitute([
            new InterviewerVacancyAssignment(
                InterviewerVacancyAssignmentId::generate(),
                InterviewerId::fromString(self::INTERVIEWER_ID),
                VacancyId::fromString(self::VACANCY_ID),
                new DateTimeImmutable('2025-01-05 10:00:00'),
                new DateTimeImmutable('2025-02-05 10:00:00'),
                2,
            ),
        ]);
    }

    /** @param InterviewerVacancyAssignment[] $assignments */
    private function reconstitute(array $assignments): Interviewer
    {
        return Interviewer::reconstitute(
            InterviewerId::fromString(self::INTERVIEWER_ID),
            EmployerId::fromString(self::EMPLOYER_ID),
            'Alice Smith',
            null,
            null,
            true,
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-01-01 10:00:00'),
            1,
            null,
            $assignments,
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

    private function insertVacancy(): void
    {
        DB::table('vacancies')->insert([
            'id' => self::VACANCY_ID,
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
