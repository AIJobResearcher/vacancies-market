<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Content;
use App\Domain\Entities\Source;
use App\Domain\Entities\Vacancy;
use App\Domain\Enums\ContentTypeEnum;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\Exceptions\StateConflictException\JobAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\JobNotAssignedException;
use App\Domain\Exceptions\StateConflictException\RequirementAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\RequirementNotAssignedException;
use App\Domain\Exceptions\StateConflictException\SourceNotAssignedException;
use App\Domain\Exceptions\StateConflictException\VacancyAlreadyClosedException;
use App\Domain\Exceptions\StateConflictException\VacancyAlreadyOpenException;
use App\Domain\Exceptions\ValidationException\SalaryMaxLessThanMinException;
use App\Domain\Exceptions\ValidationException\SalaryMaxNegativeException;
use App\Domain\Exceptions\ValidationException\SalaryMinNegativeException;
use App\Domain\Exceptions\ValidationException\VacancyRequiresJobException;
use App\Domain\Exceptions\ValidationException\VacancyRequiresSourceException;
use App\Domain\Exceptions\ValidationException\VacancyTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\ContentId;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\SourceId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use Closure;
use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VacancyTest extends TestCase
{
    private VacancyId $vacancyId;

    private EmployerId $employerId;

    #[Override]
    protected function setUp(): void
    {
        $this->vacancyId = VacancyId::generate();
        $this->employerId = EmployerId::generate();
    }

    /**
     * @return array<string, array{0: string, 1: class-string<\Throwable>}>
     */
    public static function invalidCreateProvider(): array
    {
        return [
            'empty title' => ['', VacancyTitleEmptyException::class],
        ];
    }

    /**
     * @return array<string, array{0: int, 1: int|null, 2: class-string<\Throwable>}>
     */
    public static function invalidSalaryProvider(): array
    {
        return [
            'min negative' => [-10, null, SalaryMinNegativeException::class],
            'max negative' => [1000, -500, SalaryMaxNegativeException::class],
            'max less than min' => [2000, 1500, SalaryMaxLessThanMinException::class],
        ];
    }

    /**
     * @return array<string, array{0: int, 1: int|null}>
     */
    public static function acceptedSalaryProvider(): array
    {
        return [
            'min only' => [1000, null],
            'max equals min' => [1000, 1000],
            'zero min and zero max' => [0, 0],
        ];
    }

    /**
     * @return array<string, array{0: Closure}>
     */
    public static function mutationProvider(): array
    {
        return [
            'updateDetails' => [static function (Vacancy $vacancy): void {
                $vacancy->updateVacancy('Updated title');
            }],
            'close' => [static function (Vacancy $vacancy): void {
                $vacancy->closeVacancy();
            }],
            'mergeFrom' => [static function (Vacancy $vacancy): void {
                $vacancy->mergeFrom(Vacancy::reconstitute(
                    VacancyId::generate(),
                    EmployerId::generate(),
                    'Merged title',
                    1000,
                    2000,
                    VacancyStatusEnum::OPEN,
                    [EmploymentTypeEnum::CONTRACT],
                    [WorkplaceEnum::HYBRID],
                    [1],
                    new DateTimeImmutable(),
                    new DateTimeImmutable(),
                    null,
                    1,
                    [RequirementId::generate()],
                ));
            }],
            'addRequirement' => [static function (Vacancy $vacancy): void {
                $vacancy->assignRequirement(RequirementId::generate());
            }],
            'removeRequirement' => [static function (Vacancy $vacancy): void {
                $vacancy->unassignRequirement($vacancy->requirementIds()[0]);
            }],
            'syncRequirements' => [static function (Vacancy $vacancy): void {
                $vacancy->syncRequirements([RequirementId::generate()]);
            }],
            'assignToJob' => [static function (Vacancy $vacancy): void {
                $vacancy->assignToJob(JobId::generate());
            }],
            'unassignFromJob' => [static function (Vacancy $vacancy): void {
                $vacancy->unassignFromJob($vacancy->jobIds()[0]);
            }],
            'addSource' => [static function (Vacancy $vacancy): void {
                $vacancy->addSource(self::newSource($vacancy->id(), 'https://linkedin.test/vacancies/3'));
            }],
            'updateSource' => [static function (Vacancy $vacancy): void {
                $existing = $vacancy->sources()[0];
                $vacancy->updateSource(self::newSource(
                    $vacancy->id(),
                    $existing->externalUrl(),
                    $existing->id(),
                    'Replaced title',
                ));
            }],
            'removeSource' => [static function (Vacancy $vacancy): void {
                $vacancy->removeSource($vacancy->sources()[0]->id());
            }],
        ];
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('invalidCreateProvider')]
    public function testCreateInvalid(string $title, string $exceptionClass): void
    {
        $this->expectException($exceptionClass);
        Vacancy::createVacancy($this->vacancyId, $this->employerId, $title);
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('invalidSalaryProvider')]
    public function testCreateInvalidSalary(int $minSalary, ?int $maxSalary, string $exceptionClass): void
    {
        $this->expectException($exceptionClass);
        Vacancy::createVacancy($this->vacancyId, $this->employerId, 'Software Engineer', $minSalary, $maxSalary);
    }

    #[DataProvider('acceptedSalaryProvider')]
    public function testCreateAcceptsValidSalaryPair(int $minSalary, ?int $maxSalary): void
    {
        $vacancy = Vacancy::createVacancy(
            $this->vacancyId,
            $this->employerId,
            'Software Engineer',
            $minSalary,
            $maxSalary,
            jobIds: [JobId::generate()],
            sources: [$this->source()],
        );

        $this->assertSame($minSalary, $vacancy->minSalary());
        $this->assertSame($maxSalary, $vacancy->maxSalary());
    }

    public function testCreateValid(): void
    {
        $vacancy = $this->createVacancy();

        $this->assertEquals('Software Engineer', $vacancy->title());
        $this->assertEquals($this->employerId->value(), $vacancy->employerId()->value());
        $this->assertEquals(VacancyStatusEnum::OPEN->value, $vacancy->status());
        $this->assertEquals(1000, $vacancy->minSalary());
        $this->assertEquals(2000, $vacancy->maxSalary());
        $this->assertEquals([EmploymentTypeEnum::FULL_TIME], $vacancy->employmentTypes());
        $this->assertEquals([WorkplaceEnum::REMOTE], $vacancy->workplaces());
        $this->assertEquals([804], $vacancy->researcherLocationIds());
        $this->assertEquals(1, $vacancy->version());
        $this->assertNull($vacancy->closedAt());
    }

    public function testCreateAssignsJobsAndSources(): void
    {
        $jobId = JobId::generate();
        $source = self::newSource($this->vacancyId, 'https://linkedin.test/vacancies/9');

        $vacancy = Vacancy::createVacancy(
            $this->vacancyId,
            $this->employerId,
            'Software Engineer',
            1000,
            2000,
            [EmploymentTypeEnum::FULL_TIME],
            [WorkplaceEnum::REMOTE],
            [804],
            [$jobId],
            [$source],
        );

        $this->assertEquals([$jobId], $vacancy->jobIds());
        $this->assertSame([$source], $vacancy->sources());
        $this->assertEquals([], $vacancy->requirementIds());
    }

    public function testCreateUsesDefaultsAndTrimsTitle(): void
    {
        $vacancy = Vacancy::createVacancy(
            $this->vacancyId,
            $this->employerId,
            '  Software Engineer  ',
            jobIds: [JobId::generate()],
            sources: [$this->source()],
        );

        $this->assertSame('Software Engineer', $vacancy->title());
        $this->assertSame(0, $vacancy->minSalary());
        $this->assertNull($vacancy->maxSalary());
        $this->assertSame([], $vacancy->employmentTypes());
        $this->assertSame([], $vacancy->workplaces());
        $this->assertSame([], $vacancy->researcherLocationIds());
        $this->assertSame(VacancyStatusEnum::OPEN->value, $vacancy->status());
        $this->assertSame(1, $vacancy->version());
    }

    public function testCreateWithoutJobsThrows(): void
    {
        $this->expectException(VacancyRequiresJobException::class);

        Vacancy::createVacancy(
            $this->vacancyId,
            $this->employerId,
            'Software Engineer',
            1000,
            2000,
            sources: [$this->source()],
        );
    }

    public function testCreateWithoutSourcesThrows(): void
    {
        $this->expectException(VacancyRequiresSourceException::class);

        Vacancy::createVacancy(
            $this->vacancyId,
            $this->employerId,
            'Software Engineer',
            1000,
            2000,
            jobIds: [JobId::generate()],
        );
    }

    /**
     * @param Closure(Vacancy): void $mutation
     */
    #[DataProvider('mutationProvider')]
    public function testEveryMutationBumpsVersionAndMovesUpdatedAt(Closure $mutation): void
    {
        $vacancy = $this->richVacancy();
        $version = $vacancy->version();
        $updatedAt = $vacancy->updatedAt();

        $mutation($vacancy);

        $this->assertSame($version + 1, $vacancy->version());
        $this->assertNotSame($updatedAt, $vacancy->updatedAt());
    }

    public function testUpdateVacancyChangesOnlyProvidedFields(): void
    {
        $vacancy = $this->createVacancy();

        $vacancy->updateVacancy(title: 'Updated title');

        $this->assertSame('Updated title', $vacancy->title());
        $this->assertSame(1000, $vacancy->minSalary());
        $this->assertSame(2000, $vacancy->maxSalary());
        $this->assertEquals([EmploymentTypeEnum::FULL_TIME], $vacancy->employmentTypes());
        $this->assertEquals([WorkplaceEnum::REMOTE], $vacancy->workplaces());
        $this->assertEquals([804], $vacancy->researcherLocationIds());
        $this->assertSame(2, $vacancy->version());
    }

    public function testUpdateVacancyReplacesProvidedCollections(): void
    {
        $vacancy = $this->createVacancy();

        $vacancy->updateVacancy(
            employmentTypes: [EmploymentTypeEnum::CONTRACT],
            workplaces: [WorkplaceEnum::HYBRID],
            researcherLocationIds: [1, 2],
        );

        $this->assertEquals([EmploymentTypeEnum::CONTRACT], $vacancy->employmentTypes());
        $this->assertEquals([WorkplaceEnum::HYBRID], $vacancy->workplaces());
        $this->assertEquals([1, 2], $vacancy->researcherLocationIds());
        $this->assertSame('Software Engineer', $vacancy->title());
    }

    public function testUpdateVacancyAcceptsEmptyCollections(): void
    {
        $vacancy = $this->createVacancy();

        $vacancy->updateVacancy(
            employmentTypes: [],
            workplaces: [],
            researcherLocationIds: [],
        );

        $this->assertSame([], $vacancy->employmentTypes());
        $this->assertSame([], $vacancy->workplaces());
        $this->assertSame([], $vacancy->researcherLocationIds());
    }

    public function testUpdateVacancyEmptyTitleThrows(): void
    {
        $this->expectException(VacancyTitleEmptyException::class);

        $this->createVacancy()->updateVacancy('   ');
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('invalidSalaryProvider')]
    public function testUpdateVacancyInvalidSalaryThrows(int $minSalary, ?int $maxSalary, string $exceptionClass): void
    {
        $vacancy = $this->createVacancy();

        $this->expectException($exceptionClass);

        $vacancy->updateVacancy(minSalary: $minSalary, maxSalary: $maxSalary);
    }

    public function testUpdateVacancyValidatesResultingSalaryPair(): void
    {
        $vacancy = $this->createVacancy();

        $this->expectException(SalaryMaxLessThanMinException::class);

        $vacancy->updateVacancy(minSalary: 3000);
    }

    public function testUpdateVacancySalaryOnlyChangeKeepsOtherFields(): void
    {
        $vacancy = $this->createVacancy();

        $vacancy->updateVacancy(minSalary: 1500, maxSalary: 2500);

        $this->assertSame(1500, $vacancy->minSalary());
        $this->assertSame(2500, $vacancy->maxSalary());
        $this->assertSame('Software Engineer', $vacancy->title());
        $this->assertEquals([EmploymentTypeEnum::FULL_TIME], $vacancy->employmentTypes());
        $this->assertEquals([WorkplaceEnum::REMOTE], $vacancy->workplaces());
        $this->assertEquals([804], $vacancy->researcherLocationIds());
    }

    public function testCloseVacancySetsClosedState(): void
    {
        $vacancy = $this->createVacancy();
        $createdUpdatedAt = $vacancy->updatedAt();

        $vacancy->closeVacancy();

        $this->assertSame(VacancyStatusEnum::CLOSED->value, $vacancy->status());
        $this->assertInstanceOf(DateTimeImmutable::class, $vacancy->closedAt());
        $this->assertSame($vacancy->closedAt(), $vacancy->updatedAt());
        $this->assertNotSame($createdUpdatedAt, $vacancy->updatedAt());
        $this->assertSame(2, $vacancy->version());
    }

    public function testCloseVacancyTwiceThrows(): void
    {
        $vacancy = $this->createVacancy();
        $vacancy->closeVacancy();

        $this->expectException(VacancyAlreadyClosedException::class);

        $vacancy->closeVacancy();
    }

    public function testReopenVacancyClearsClosedAtAndBumpsVersion(): void
    {
        $vacancy = $this->createVacancy();
        $vacancy->closeVacancy();
        $closedUpdatedAt = $vacancy->updatedAt();

        $vacancy->reopenVacancy();

        $this->assertSame(VacancyStatusEnum::OPEN->value, $vacancy->status());
        $this->assertNull($vacancy->closedAt());
        $this->assertNotSame($closedUpdatedAt, $vacancy->updatedAt());
        $this->assertSame(3, $vacancy->version());
    }

    public function testReopenVacancyWhenOpenThrows(): void
    {
        $this->expectException(VacancyAlreadyOpenException::class);

        $this->createVacancy()->reopenVacancy();
    }

    public function testMergeFromTakesCanonicalFieldsAndMergesRequirements(): void
    {
        $shared = RequirementId::generate();
        $own = RequirementId::generate();
        $other = RequirementId::generate();
        $jobId = JobId::generate();
        $vacancy = $this->reconstitutedVacancy([$shared, $own], [$jobId]);

        $source = Vacancy::reconstitute(
            VacancyId::generate(),
            EmployerId::generate(),
            'Canonical title',
            3000,
            4000,
            VacancyStatusEnum::CLOSED,
            [EmploymentTypeEnum::CONTRACT],
            [WorkplaceEnum::HYBRID],
            [1, 2],
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-02-01 10:00:00'),
            9,
            [$shared, $other],
        );

        $vacancy->mergeFrom($source);

        $this->assertSame('Canonical title', $vacancy->title());
        $this->assertSame(3000, $vacancy->minSalary());
        $this->assertSame(4000, $vacancy->maxSalary());
        $this->assertEquals([EmploymentTypeEnum::CONTRACT], $vacancy->employmentTypes());
        $this->assertEquals([WorkplaceEnum::HYBRID], $vacancy->workplaces());
        $this->assertEquals([1, 2], $vacancy->researcherLocationIds());
        $this->assertEquals([$shared, $own, $other], $vacancy->requirementIds());
        $this->assertEquals([$jobId], $vacancy->jobIds());
        $this->assertSame([], $vacancy->sources());
        $this->assertSame(VacancyStatusEnum::OPEN->value, $vacancy->status());
        $this->assertSame(2, $vacancy->version());
    }

    public function testAssignRequirementDuplicateThrows(): void
    {
        $requirementId = RequirementId::generate();
        $vacancy = $this->reconstitutedVacancy([$requirementId]);

        $this->expectException(RequirementAlreadyAssignedException::class);

        $vacancy->assignRequirement($requirementId);
    }

    public function testUnassignRequirementNotAssignedThrows(): void
    {
        $vacancy = $this->reconstitutedVacancy([RequirementId::generate()]);

        $this->expectException(RequirementNotAssignedException::class);

        $vacancy->unassignRequirement(RequirementId::generate());
    }

    public function testUnassignRequirementRemovesOnlyGivenId(): void
    {
        $removed = RequirementId::generate();
        $kept = RequirementId::generate();
        $vacancy = $this->reconstitutedVacancy([$removed, $kept]);

        $vacancy->unassignRequirement($removed);

        $this->assertEquals([$kept], $vacancy->requirementIds());
        $this->assertSame(2, $vacancy->version());
    }

    public function testSyncRequirementsReplacesSet(): void
    {
        $removed = RequirementId::generate();
        $kept = RequirementId::generate();
        $added = RequirementId::generate();
        $vacancy = $this->reconstitutedVacancy([$removed, $kept]);

        $vacancy->syncRequirements([$kept, $added]);

        $this->assertEquals([$kept, $added], $vacancy->requirementIds());
    }

    public function testSyncRequirementsSkipsDuplicateInsideOneCall(): void
    {
        $existing = RequirementId::generate();
        $added = RequirementId::generate();
        $vacancy = $this->reconstitutedVacancy([$existing]);

        $vacancy->syncRequirements([$added, $added]);

        $this->assertEquals([$added], $vacancy->requirementIds());
    }

    public function testSyncRequirementsIsNoOpWhenSetUnchanged(): void
    {
        $first = RequirementId::generate();
        $second = RequirementId::generate();
        $vacancy = $this->reconstitutedVacancy([$first, $second]);
        $version = $vacancy->version();
        $updatedAt = $vacancy->updatedAt();

        $vacancy->syncRequirements([$first, $second]);

        $this->assertEquals([$first, $second], $vacancy->requirementIds());
        $this->assertSame($version, $vacancy->version());
        $this->assertSame($updatedAt, $vacancy->updatedAt());
    }

    public function testAssignToJobDuplicateThrows(): void
    {
        $jobId = JobId::generate();
        $vacancy = $this->reconstitutedVacancy([], [$jobId]);

        $this->expectException(JobAlreadyAssignedException::class);

        $vacancy->assignToJob($jobId);
    }

    public function testUnassignFromJobRemovesLink(): void
    {
        $removed = JobId::generate();
        $kept = JobId::generate();
        $vacancy = $this->reconstitutedVacancy([], [$removed, $kept]);

        $vacancy->unassignFromJob($removed);

        $this->assertEquals([$kept], $vacancy->jobIds());
        $this->assertSame(2, $vacancy->version());
    }

    public function testUnassignFromJobUnknownThrowsJobNotAssigned(): void
    {
        $vacancy = $this->reconstitutedVacancy([], [JobId::generate(), JobId::generate()]);

        $this->expectException(JobNotAssignedException::class);

        $vacancy->unassignFromJob(JobId::generate());
    }

    public function testUnassignFromJobLastJobThrows(): void
    {
        $jobId = JobId::generate();
        $vacancy = $this->reconstitutedVacancy([], [$jobId]);

        $this->expectException(VacancyRequiresJobException::class);

        $vacancy->unassignFromJob($jobId);
    }

    public function testAddSourceAddsNewSource(): void
    {
        $vacancy = $this->createVacancy();
        $source = self::newSource($this->vacancyId, 'https://linkedin.test/vacancies/2');

        $vacancy->addSource($source);

        $this->assertCount(2, $vacancy->sources());
        $this->assertSame($source, $vacancy->sources()[1]);
        $this->assertSame(2, $vacancy->version());
    }

    public function testAddSourceWithSameExternalUrlRefreshesExisting(): void
    {
        $vacancy = $this->createVacancy();
        $existing = $vacancy->sources()[0];
        $newPortalId = PortalId::generate();
        $newPostedAt = new DateTimeImmutable('2025-06-01 10:00:00');
        $refreshed = self::newSource(
            $this->vacancyId,
            $existing->externalUrl(),
            SourceId::generate(),
            'Refreshed title',
            $newPortalId,
            $newPostedAt,
        );

        $vacancy->addSource($refreshed);

        $this->assertCount(1, $vacancy->sources());
        $this->assertSame($existing, $vacancy->sources()[0]);
        $this->assertSame($newPortalId, $existing->portalId());
        $this->assertSame('Refreshed title', $existing->title());
        $this->assertSame($newPostedAt, $existing->postedAt());
        $this->assertNotSame($existing->id(), $refreshed->id());
        $this->assertSame(2, $vacancy->version());
    }

    public function testUpdateSourceReplacesById(): void
    {
        $vacancy = $this->createVacancy();
        $existing = $vacancy->sources()[0];
        $replacement = self::newSource(
            $this->vacancyId,
            'https://linkedin.test/vacancies/updated',
            $existing->id(),
            'Replaced title',
        );

        $vacancy->updateSource($replacement);

        $this->assertCount(1, $vacancy->sources());
        $this->assertSame($replacement, $vacancy->sources()[0]);
        $this->assertSame(2, $vacancy->version());
    }

    public function testUpdateSourceUnknownThrows(): void
    {
        $vacancy = $this->createVacancy();
        $unknown = self::newSource($this->vacancyId, 'https://linkedin.test/vacancies/unknown');

        $this->expectException(SourceNotAssignedException::class);

        $vacancy->updateSource($unknown);
    }

    public function testRemoveSourceRemovesGivenSource(): void
    {
        $vacancy = $this->reconstitutedVacancy([], [], [
            self::newSource($this->vacancyId, 'https://linkedin.test/vacancies/1'),
            self::newSource($this->vacancyId, 'https://linkedin.test/vacancies/2'),
        ]);
        $removed = $vacancy->sources()[0];
        $kept = $vacancy->sources()[1];

        $vacancy->removeSource($removed->id());

        $this->assertSame([$kept], $vacancy->sources());
        $this->assertSame(2, $vacancy->version());
    }

    public function testRemoveSourceUnknownThrows(): void
    {
        $vacancy = $this->reconstitutedVacancy([], [], [
            self::newSource($this->vacancyId, 'https://linkedin.test/vacancies/1'),
        ]);

        $this->expectException(SourceNotAssignedException::class);

        $vacancy->removeSource(SourceId::generate());
    }

    public function testRemoveSourceLastThrowsVacancyRequiresSource(): void
    {
        $vacancy = $this->createVacancy();

        $this->expectException(VacancyRequiresSourceException::class);

        $vacancy->removeSource($vacancy->sources()[0]->id());
    }

    public function testReconstituteRestoresFullState(): void
    {
        $id = VacancyId::generate();
        $employerId = EmployerId::generate();
        $createdAt = new DateTimeImmutable('2025-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2025-02-01 10:00:00');
        $closedAt = new DateTimeImmutable('2025-03-01 10:00:00');

        $vacancy = Vacancy::reconstitute(
            $id,
            $employerId,
            'Closed Senior Role',
            2000,
            3000,
            VacancyStatusEnum::CLOSED,
            [EmploymentTypeEnum::FULL_TIME],
            [WorkplaceEnum::REMOTE],
            [804],
            $createdAt,
            $updatedAt,
            $closedAt,
            7
        );

        $this->assertEquals($id, $vacancy->id());
        $this->assertEquals($employerId, $vacancy->employerId());
        $this->assertEquals(VacancyStatusEnum::CLOSED->value, $vacancy->status());
        $this->assertEquals(7, $vacancy->version());
        $this->assertEquals(2000, $vacancy->minSalary());
        $this->assertEquals(3000, $vacancy->maxSalary());
        $this->assertEquals([EmploymentTypeEnum::FULL_TIME], $vacancy->employmentTypes());
        $this->assertEquals([WorkplaceEnum::REMOTE], $vacancy->workplaces());
        $this->assertEquals([804], $vacancy->researcherLocationIds());
        $this->assertSame($createdAt, $vacancy->createdAt());
        $this->assertSame($updatedAt, $vacancy->updatedAt());
        $this->assertSame($closedAt, $vacancy->closedAt());
        $this->assertEquals([], $vacancy->requirementIds());
        $this->assertEquals([], $vacancy->jobIds());
        $this->assertEquals([], $vacancy->sources());
    }

    public function testReconstitutePopulatesChildCollections(): void
    {
        $id = VacancyId::generate();
        $employerId = EmployerId::generate();
        $now = new DateTimeImmutable('2025-01-01 10:00:00');

        $requirementId = RequirementId::generate();
        $jobId = JobId::generate();
        $source = Source::reconstitute(
            SourceId::generate(),
            $id,
            PortalId::generate(),
            'ext123',
            'https://linkedin.com/123',
            'Senior PHP',
            $now,
            $now,
            $now
        );

        $vacancy = Vacancy::reconstitute(
            $id,
            $employerId,
            'Role',
            1000,
            2000,
            VacancyStatusEnum::OPEN,
            [EmploymentTypeEnum::FULL_TIME],
            [WorkplaceEnum::REMOTE],
            [],
            $now,
            $now,
            null,
            2,
            [$requirementId],
            [$jobId],
            [$source]
        );

        $this->assertSame([$requirementId], $vacancy->requirementIds());
        $this->assertSame([$jobId], $vacancy->jobIds());
        $this->assertSame([$source], $vacancy->sources());
    }

    public function testReconstituteSkipsValidationAndDefaulting(): void
    {
        $createdAt = new DateTimeImmutable('2025-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2025-02-01 10:00:00');
        $closedAt = new DateTimeImmutable('2025-03-01 10:00:00');

        $vacancy = Vacancy::reconstitute(
            $this->vacancyId,
            $this->employerId,
            'Untouched Role',
            5000,
            100,
            VacancyStatusEnum::CLOSED,
            [],
            [],
            [],
            $createdAt,
            $updatedAt,
            $closedAt,
            12,
            [],
            [],
            []
        );

        $this->assertSame(5000, $vacancy->minSalary());
        $this->assertSame(100, $vacancy->maxSalary());
        $this->assertSame(12, $vacancy->version());
        $this->assertSame(VacancyStatusEnum::CLOSED->value, $vacancy->status());
        $this->assertSame([], $vacancy->requirementIds());
        $this->assertSame([], $vacancy->jobIds());
        $this->assertSame([], $vacancy->sources());
        $this->assertSame($createdAt, $vacancy->createdAt());
        $this->assertSame($updatedAt, $vacancy->updatedAt());
        $this->assertSame($closedAt, $vacancy->closedAt());
    }

    public function testToArrayExposesDocumentedShape(): void
    {
        $requirementId = RequirementId::generate();
        $jobId = JobId::generate();
        $sourceId = SourceId::generate();
        $source = self::newSource(
            $this->vacancyId,
            'https://linkedin.test/vacancies/1',
            $sourceId,
            'Senior PHP',
            null,
            null,
            'ext-1',
        );
        $source->addContent(Content::createContent(
            ContentId::generate(),
            $sourceId,
            ContentTypeEnum::DESCRIPTION,
            'Full description',
        ));
        $createdAt = new DateTimeImmutable('2025-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2025-02-01 10:00:00');

        $vacancy = Vacancy::reconstitute(
            $this->vacancyId,
            $this->employerId,
            'Senior PHP',
            2000,
            3000,
            VacancyStatusEnum::OPEN,
            [EmploymentTypeEnum::FULL_TIME],
            [WorkplaceEnum::REMOTE],
            [804],
            $createdAt,
            $updatedAt,
            null,
            4,
            [$requirementId],
            [$jobId],
            [$source],
        );

        $array = $vacancy->toArray();

        $this->assertSame($this->vacancyId->value(), $array['id']);
        $this->assertSame($this->employerId->value(), $array['employer_id']);
        $this->assertSame('Senior PHP', $array['title']);
        $this->assertSame(2000, $array['min_salary']);
        $this->assertSame(3000, $array['max_salary']);
        $this->assertSame(VacancyStatusEnum::OPEN->value, $array['status']);
        $this->assertSame([EmploymentTypeEnum::FULL_TIME->value], $array['employment_types']);
        $this->assertSame([WorkplaceEnum::REMOTE->value], $array['workplaces']);
        $this->assertSame([804], $array['researcher_location_ids']);
        $this->assertSame($createdAt->format(DATE_ATOM), $array['created_at']);
        $this->assertSame($updatedAt->format(DATE_ATOM), $array['updated_at']);
        $this->assertNull($array['closed_at']);
        $this->assertSame(4, $array['version']);
        $this->assertSame([$requirementId->value()], $array['requirements']);
        $this->assertSame([$jobId->value()], $array['jobs']);
        $this->assertSame([$source->toArray()], $array['sources']);
        $this->assertSame('ext-1', $array['sources'][0]['external_vacancy_id']);
        $this->assertSame(
            [[
                'id' => $source->contents()[0]->id()->value(),
                'type' => ContentTypeEnum::DESCRIPTION->value,
                'value' => 'Full description',
            ]],
            $array['sources'][0]['contents'],
        );
    }

    public function testClearMaxSalaryResetsToNull(): void
    {
        $vacancy = $this->createVacancy();
        $version = $vacancy->version();
        $updatedAt = $vacancy->updatedAt();

        $vacancy->clearMaxSalary();

        $this->assertNull($vacancy->maxSalary());
        $this->assertSame(1000, $vacancy->minSalary());
        $this->assertSame($version + 1, $vacancy->version());
        $this->assertNotSame($updatedAt, $vacancy->updatedAt());
    }

    public function testClearMaxSalaryIsNoOpWhenAlreadyNull(): void
    {
        $vacancy = Vacancy::createVacancy(
            $this->vacancyId,
            $this->employerId,
            'Software Engineer',
            1000,
            jobIds: [JobId::generate()],
            sources: [$this->source()],
        );
        $version = $vacancy->version();
        $updatedAt = $vacancy->updatedAt();

        $vacancy->clearMaxSalary();

        $this->assertNull($vacancy->maxSalary());
        $this->assertSame($version, $vacancy->version());
        $this->assertSame($updatedAt, $vacancy->updatedAt());
    }

    private function createVacancy(): Vacancy
    {
        return Vacancy::createVacancy(
            $this->vacancyId,
            $this->employerId,
            'Software Engineer',
            1000,
            2000,
            [EmploymentTypeEnum::FULL_TIME],
            [WorkplaceEnum::REMOTE],
            [804],
            [JobId::generate()],
            [$this->source()],
        );
    }

    private function richVacancy(): Vacancy
    {
        return $this->reconstitutedVacancy(
            [RequirementId::generate(), RequirementId::generate()],
            [JobId::generate(), JobId::generate()],
            [
                self::newSource($this->vacancyId, 'https://linkedin.test/vacancies/1'),
                self::newSource($this->vacancyId, 'https://linkedin.test/vacancies/2'),
            ],
        );
    }

    /**
     * @param list<RequirementId> $requirementIds
     * @param list<JobId> $jobIds
     * @param list<Source> $sources
     */
    private function reconstitutedVacancy(
        array $requirementIds = [],
        array $jobIds = [],
        array $sources = [],
        int $version = 1,
    ): Vacancy {
        $timestamp = new DateTimeImmutable('2025-01-01 10:00:00');

        return Vacancy::reconstitute(
            $this->vacancyId,
            $this->employerId,
            'Reconstituted Role',
            1000,
            2000,
            VacancyStatusEnum::OPEN,
            [EmploymentTypeEnum::FULL_TIME],
            [WorkplaceEnum::REMOTE],
            [804],
            $timestamp,
            $timestamp,
            null,
            $version,
            $requirementIds,
            $jobIds,
            $sources,
        );
    }

    private function source(): Source
    {
        return self::newSource($this->vacancyId, 'https://linkedin.test/vacancies/1');
    }

    private static function newSource(
        VacancyId $vacancyId,
        string $externalUrl,
        ?SourceId $id = null,
        string $title = 'Senior PHP',
        ?PortalId $portalId = null,
        ?DateTimeImmutable $postedAt = null,
        ?string $externalVacancyId = null,
    ): Source {
        $timestamp = new DateTimeImmutable('2025-01-01 10:00:00');

        return Source::reconstitute(
            $id ?? SourceId::generate(),
            $vacancyId,
            $portalId ?? PortalId::generate(),
            $externalVacancyId,
            $externalUrl,
            $title,
            $postedAt ?? $timestamp,
            $timestamp,
            $timestamp,
        );
    }
}
