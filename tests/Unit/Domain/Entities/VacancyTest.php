<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Vacancy;
use App\Domain\Entities\VacancySource;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\Events\VacancyClosedEvent;
use App\Domain\Events\VacancyImportedEvent;
use App\Domain\Events\VacancyMergedEvent;
use App\Domain\Events\VacancyUpdatedEvent;
use App\Domain\Exceptions\StateConflictException\JobAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\JobNotAssignedException;
use App\Domain\Exceptions\StateConflictException\RequirementAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\RequirementNotAssignedException;
use App\Domain\Exceptions\StateConflictException\VacancyAlreadyClosedException;
use App\Domain\Exceptions\StateConflictException\VacancyAlreadyOpenException;
use App\Domain\Exceptions\ValidationException\VacancyTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\EntityIds\VacancySourceId;
use App\Domain\ValueObjects\ExternalUrls;
use App\Domain\ValueObjects\Salary;
use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VacancyTest extends TestCase
{
    private VacancyId $vacancyId;

    private EmployerId $employerId;

    private Salary $salary;

    private ExternalUrls $urls;

    #[Override]
    protected function setUp(): void
    {
        $this->vacancyId = VacancyId::generate();
        $this->employerId = EmployerId::generate();
        $this->salary = new Salary(1000, 2000);
        $this->urls = new ExternalUrls(['https://example.com/vacancy']);
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: string,
     *     2: string|null,
     *     3: string|null,
     *     4: EmploymentTypeEnum,
     *     5: WorkplaceEnum,
     *     6: string
     * }>
     */
    public static function invalidCreateProvider(): array
    {
        return [
            'empty title' => [
                '',
                'desc',
                null,
                null,
                EmploymentTypeEnum::FULL_TIME,
                WorkplaceEnum::REMOTE,
                VacancyTitleEmptyException::class,
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     0: string|null,
     *     1: string|null,
     *     2: int|null,
     *     3: int|null,
     *     4: string|null,
     *     5: string|null,
     *     6: EmploymentTypeEnum|null,
     *     7: WorkplaceEnum|null,
     *     8: string|null,
     *     9: string[]|null,
     *     10: string|null
     * }>
     */
    public static function updateDetailsProvider(): array
    {
        return [
            'all fields' => [
                'Senior', 'New desc', 1500, 2500, 'Canada', 'Toronto',
                EmploymentTypeEnum::CONTRACT, WorkplaceEnum::HYBRID, '2025-02-01',
                ['https://new.com'], 'internal-new',
            ],
            'only title' => ['Senior', null, null, null, null, null, null, null, null, null, null],
            'only salary' => [null, null, 3000, 4000, null, null, null, null, null, null, null],
            'only urls' => [null, null, null, null, null, null, null, null, null, ['https://new.com'], null],
        ];
    }

    private function createVacancy(): Vacancy
    {
        return Vacancy::create(
            $this->vacancyId,
            $this->employerId,
            'Software Engineer',
            'Description',
            $this->salary,
            'USA',
            'NYC',
            EmploymentTypeEnum::FULL_TIME,
            WorkplaceEnum::REMOTE,
            new DateTimeImmutable('2025-01-01'),
            $this->urls,
            null,
            'corr-id'
        );
    }

    /**
     * @param  class-string<\Throwable>  $exceptionClass
     */
    #[DataProvider('invalidCreateProvider')]
    public function test_create_invalid(
        string $title,
        string $desc,
        ?string $country,
        ?string $city,
        EmploymentTypeEnum $employmentType,
        WorkplaceEnum $workplace,
        string $exceptionClass
    ): void {
        $this->expectException($exceptionClass);
        Vacancy::create(
            $this->vacancyId,
            $this->employerId,
            $title,
            $desc,
            $this->salary,
            $country,
            $city,
            $employmentType,
            $workplace,
            new DateTimeImmutable,
            $this->urls
        );
    }

    public function test_create_valid(): void
    {
        $vacancy = $this->createVacancy();
        $this->assertEquals('Software Engineer', $vacancy->title());
        $this->assertEquals('Description', $vacancy->description());
        $this->assertEquals($this->employerId->value(), $vacancy->employerId()->value());
        $this->assertEquals(VacancyStatusEnum::OPEN->value, $vacancy->status());
        $this->assertEquals('USA', $vacancy->country());
        $this->assertEquals('NYC', $vacancy->city());
        $this->assertEquals(EmploymentTypeEnum::FULL_TIME, $vacancy->employmentType());
        $this->assertEquals(WorkplaceEnum::REMOTE, $vacancy->workplace());
        $this->assertEquals(1, $vacancy->version());
        $this->assertEquals($this->salary, $vacancy->salary());
        $this->assertEquals($this->urls, $vacancy->externalUrls());
        $this->assertNull($vacancy->internalUrl());
        $this->assertNull($vacancy->closedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $vacancy->createdAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $vacancy->updatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $vacancy->postedAt());

        $events = $vacancy->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(VacancyImportedEvent::class, $events[0]);
        $this->assertEquals('corr-id', $events[0]->correlationId);
        $this->assertEquals(1, $events[0]->eventVersion);
        $this->assertEquals($this->vacancyId->value(), $events[0]->aggregateId);
    }

    /**
     * @param  string[]|null  $externalUrls
     */
    #[DataProvider('updateDetailsProvider')]
    public function test_update_details(
        ?string $title,
        ?string $desc,
        ?int $minSalary,
        ?int $maxSalary,
        ?string $country,
        ?string $city,
        ?EmploymentTypeEnum $employmentType,
        ?WorkplaceEnum $workplace,
        ?string $postedAt,
        ?array $externalUrls,
        ?string $internalUrl
    ): void {
        $vacancy = $this->createVacancy();
        $vacancy->releaseEvents();
        $oldVersion = $vacancy->version();

        $salary = ($minSalary !== null || $maxSalary !== null)
            ? new Salary($minSalary ?? 0, $maxSalary)
            : null;
        $urls = ($externalUrls !== null && count($externalUrls) > 0) ? new ExternalUrls($externalUrls) : null;
        $posted = ($postedAt !== null && $postedAt !== '') ? new DateTimeImmutable($postedAt) : null;

        $vacancy->updateDetails(
            $title,
            $desc,
            $salary,
            $country,
            $city,
            $employmentType,
            $workplace,
            $posted,
            $urls,
            $internalUrl
        );

        if ($title !== null) {
            $this->assertEquals($title, $vacancy->toArray()['title']);
        }
        $this->assertEquals($oldVersion + 1, $vacancy->version());

        $events = $vacancy->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(VacancyUpdatedEvent::class, $events[0]);
        $this->assertEquals(1, $events[0]->eventVersion);
    }

    public function test_update_with_empty_title_throws(): void
    {
        $vacancy = $this->createVacancy();
        $this->expectException(VacancyTitleEmptyException::class);
        $vacancy->updateDetails(title: '');
    }

    public function test_close(): void
    {
        $vacancy = $this->createVacancy();
        $vacancy->releaseEvents();
        $oldVersion = $vacancy->version();

        $vacancy->close();
        $this->assertEquals(VacancyStatusEnum::CLOSED->value, $vacancy->status());
        $this->assertNotNull($vacancy->toArray()['closed_at']);
        $this->assertEquals($oldVersion + 1, $vacancy->version());

        $events = $vacancy->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(VacancyClosedEvent::class, $events[0]);
        $this->assertEquals(1, $events[0]->eventVersion);
    }

    public function test_close_already_closed_throws(): void
    {
        $vacancy = $this->createVacancy();
        $vacancy->close();
        $this->expectException(VacancyAlreadyClosedException::class);
        $vacancy->close();
    }

    public function test_reopen(): void
    {
        $vacancy = $this->createVacancy();
        $vacancy->close();
        $vacancy->releaseEvents();
        $oldVersion = $vacancy->version();

        $vacancy->reopen();
        $this->assertEquals(VacancyStatusEnum::OPEN->value, $vacancy->status());
        $this->assertNull($vacancy->toArray()['closed_at']);
        $this->assertEquals($oldVersion + 1, $vacancy->version());

        $events = $vacancy->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(VacancyUpdatedEvent::class, $events[0]);
    }

    public function test_reopen_already_open_throws(): void
    {
        $vacancy = $this->createVacancy();
        $this->expectException(VacancyAlreadyOpenException::class);
        $vacancy->reopen();
    }

    public function test_add_requirement(): void
    {
        $vacancy = $this->createVacancy();
        $reqId = RequirementId::generate();
        $oldVersion = $vacancy->version();

        $vacancy->addRequirement($reqId);
        $this->assertContains($reqId->value(), $vacancy->toArray()['requirements']);
        $this->assertEquals($oldVersion + 1, $vacancy->version());
    }

    public function test_add_requirement_duplicate_throws(): void
    {
        $vacancy = $this->createVacancy();
        $reqId = RequirementId::generate();
        $vacancy->addRequirement($reqId);
        $this->expectException(RequirementAlreadyAssignedException::class);
        $vacancy->addRequirement($reqId);
    }

    public function test_remove_requirement(): void
    {
        $vacancy = $this->createVacancy();
        $reqId = RequirementId::generate();
        $vacancy->addRequirement($reqId);
        $oldVersion = $vacancy->version();

        $vacancy->removeRequirement($reqId);
        $this->assertNotContains($reqId->value(), $vacancy->toArray()['requirements']);
        $this->assertEquals($oldVersion + 1, $vacancy->version());
    }

    public function test_remove_requirement_not_assigned_throws(): void
    {
        $vacancy = $this->createVacancy();
        $this->expectException(RequirementNotAssignedException::class);
        $vacancy->removeRequirement(RequirementId::generate());
    }

    public function test_assign_to_job(): void
    {
        $vacancy = $this->createVacancy();
        $jobId = JobId::generate();
        $oldVersion = $vacancy->version();

        $vacancy->assignToJob($jobId, 80);
        $this->assertContains($jobId->value(), $vacancy->toArray()['jobs']);
        $this->assertEquals($oldVersion + 1, $vacancy->version());
    }

    public function test_assign_to_job_duplicate_active_throws(): void
    {
        $vacancy = $this->createVacancy();
        $jobId = JobId::generate();
        $vacancy->assignToJob($jobId);
        $this->expectException(JobAlreadyAssignedException::class);
        $vacancy->assignToJob($jobId);
    }

    public function test_unassign_from_job(): void
    {
        $vacancy = $this->createVacancy();
        $jobId = JobId::generate();
        $vacancy->assignToJob($jobId);
        $oldVersion = $vacancy->version();

        $vacancy->unassignFromJob($jobId);
        $this->assertEquals($oldVersion + 1, $vacancy->version());

        $vacancy->assignToJob($jobId);
    }

    public function test_unassign_from_job_not_assigned_throws(): void
    {
        $vacancy = $this->createVacancy();
        $this->expectException(JobNotAssignedException::class);
        $vacancy->unassignFromJob(JobId::generate());
    }

    public function test_add_source_new(): void
    {
        $vacancy = $this->createVacancy();
        $source = new VacancySource(
            VacancySourceId::generate(),
            $vacancy->id(),
            'linkedin',
            'ext123',
            'https://linkedin.com/123',
            new DateTimeImmutable,
            new DateTimeImmutable
        );
        $oldVersion = $vacancy->version();

        $vacancy->addSource($source);
        $this->assertEquals($oldVersion + 1, $vacancy->version());
    }

    public function test_add_source_existing_updates_last_seen(): void
    {
        $vacancy = $this->createVacancy();
        $source = new VacancySource(
            VacancySourceId::generate(),
            $vacancy->id(),
            'linkedin',
            'ext123',
            'https://linkedin.com/123',
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-01')
        );
        $vacancy->addSource($source);
        $oldVersion = $vacancy->version();

        $source2 = new VacancySource(
            VacancySourceId::generate(),
            $vacancy->id(),
            'linkedin',
            'ext123',
            'https://linkedin.com/123',
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-02')
        );
        $vacancy->addSource($source2);
        $this->assertEquals($oldVersion + 1, $vacancy->version());
    }

    public function test_merge_from(): void
    {
        $target = $this->createVacancy();
        $source = Vacancy::create(
            VacancyId::generate(),
            $this->employerId,
            'Merged Title',
            'Merged Desc',
            new Salary(3000, 4000),
            'Canada',
            'Toronto',
            EmploymentTypeEnum::CONTRACT,
            WorkplaceEnum::HYBRID,
            new DateTimeImmutable('2025-02-01'),
            new ExternalUrls(['https://source.com']),
            'internal-source'
        );
        $oldVersion = $target->version();
        $mergedIds = [$source->id()->value()];
        $target->releaseEvents();

        $target->mergeFrom($source, $mergedIds);

        $this->assertEquals('Merged Title', $target->toArray()['title']);
        $this->assertEquals(3000, $target->toArray()['salary']['min']);
        $this->assertEquals($oldVersion + 1, $target->version());

        $events = $target->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(VacancyMergedEvent::class, $events[0]);
        $this->assertEquals($mergedIds, $events[0]->mergedVacancyIds);
        $this->assertEquals(1, $events[0]->eventVersion);
    }
}
