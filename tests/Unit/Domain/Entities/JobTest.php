<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Job;
use App\Domain\Exceptions\StateConflictException\RequirementAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\RequirementNotAssignedException;
use App\Domain\Exceptions\ValidationException\JobTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class JobTest extends TestCase
{
    private JobId $jobId;

    private RequirementId $reqId;

    #[Override]
    protected function setUp(): void
    {
        $this->jobId = JobId::generate();
        $this->reqId = RequirementId::generate();
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: string,
     *     2: string|null,
     *     3: JobId|null,
     *     4: string|null
     * }>
     */
    public static function validCreateProvider(): array
    {
        return [
            'all fields' => ['Software Engineer', 'IT', 'Backend', null, 'Description'],
            'minimal' => ['Engineer', 'IT', null, null, null],
            'with parent' => ['Junior', 'IT', null, JobId::generate(), null],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function emptyTitleProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
        ];
    }

    #[DataProvider('validCreateProvider')]
    public function testCreateValid(
        string $title,
        string $category,
        ?string $subCategory,
        ?JobId $parent,
        ?string $desc
    ): void {
        $job = Job::createJob($this->jobId, $title, $category, $subCategory, $parent, $desc, 'corr-123');
        $this->assertSame($this->jobId, $job->id());
        $this->assertEquals($title, $job->title());
        $this->assertEquals($category, $job->category());
        $this->assertEquals($subCategory, $job->subCategory());
        $this->assertEquals($parent?->value(), $job->parentJobId()?->value());
        $this->assertEquals($desc, $job->description());
        $this->assertEquals(1, $job->version());
        $this->assertNull($job->deletedAt());
        $this->assertSame($job->createdAt(), $job->updatedAt());
    }

    public function testCreateTrimsTitle(): void
    {
        $job = Job::createJob($this->jobId, '  Engineer  ', 'IT');

        $this->assertEquals('Engineer', $job->title());
    }

    #[DataProvider('emptyTitleProvider')]
    public function testCreateEmptyTitleThrows(string $title): void
    {
        $this->expectException(JobTitleEmptyException::class);
        Job::createJob($this->jobId, $title, 'IT');
    }

    public function testUpdateJobChangesProvidedFields(): void
    {
        $parent = JobId::generate();
        $job = Job::createJob($this->jobId, 'Engineer', 'IT', 'Backend', null, 'Old desc');

        $job->updateJob('Senior Engineer', 'Engineering', 'Platform', 'New desc', $parent);

        $this->assertEquals('Senior Engineer', $job->title());
        $this->assertEquals('Engineering', $job->category());
        $this->assertEquals('Platform', $job->subCategory());
        $this->assertEquals('New desc', $job->description());
        $this->assertEquals($parent->value(), $job->parentJobId()?->value());
    }

    public function testUpdateJobKeepsOmittedFields(): void
    {
        $parent = JobId::generate();
        $job = Job::createJob($this->jobId, 'Engineer', 'IT', 'Backend', $parent, 'Old desc');

        $job->updateJob(title: 'Senior Engineer');

        $this->assertEquals('Senior Engineer', $job->title());
        $this->assertEquals('IT', $job->category());
        $this->assertEquals('Backend', $job->subCategory());
        $this->assertEquals('Old desc', $job->description());
        $this->assertEquals($parent->value(), $job->parentJobId()?->value());
    }

    public function testUpdateJobBumpsVersionAndRefreshesUpdatedAt(): void
    {
        $job = Job::createJob($this->jobId, 'Engineer', 'IT');
        $updatedAt = $job->updatedAt();

        $job->updateJob('Senior Engineer');

        $this->assertEquals(2, $job->version());
        $this->assertNotSame($updatedAt, $job->updatedAt());
    }

    public function testUpdateJobTrimsTitle(): void
    {
        $job = Job::createJob($this->jobId, 'Engineer', 'IT');
        $job->updateJob('  Senior Engineer  ');

        $this->assertEquals('Senior Engineer', $job->title());
    }

    #[DataProvider('emptyTitleProvider')]
    public function testUpdateJobWithEmptyTitleThrows(string $title): void
    {
        $job = Job::createJob($this->jobId, 'Engineer', 'IT');
        $this->expectException(JobTitleEmptyException::class);
        $job->updateJob($title);
    }

    public function testDeleteJobSetsDeletedAtAndBumpsVersion(): void
    {
        $job = Job::createJob($this->jobId, 'Engineer', 'IT');

        $job->deleteJob();

        $this->assertNotNull($job->deletedAt());
        $this->assertEquals(2, $job->version());
    }

    public function testAssignRequirement(): void
    {
        $job = Job::createJob($this->jobId, 'Engineer', 'IT');
        $oldVersion = $job->version();

        $job->assignRequirement($this->reqId);

        $this->assertEquals($oldVersion + 1, $job->version());
        $this->assertEquals([$this->reqId], $job->requirementIds());
    }

    public function testAssignDuplicateRequirementThrows(): void
    {
        $job = Job::createJob($this->jobId, 'Engineer', 'IT');
        $job->assignRequirement($this->reqId);
        $this->expectException(RequirementAlreadyAssignedException::class);
        $job->assignRequirement($this->reqId);
    }

    public function testUnassignRequirement(): void
    {
        $job = Job::createJob($this->jobId, 'Engineer', 'IT');
        $job->assignRequirement($this->reqId);
        $oldVersion = $job->version();

        $job->unassignRequirement($this->reqId);

        $this->assertEquals($oldVersion + 1, $job->version());
        $this->assertEquals([], $job->requirementIds());
        // adding again works
        $job->assignRequirement($this->reqId);
    }

    public function testUnassignNonExistentRequirementThrows(): void
    {
        $job = Job::createJob($this->jobId, 'Engineer', 'IT');
        $this->expectException(RequirementNotAssignedException::class);
        $job->unassignRequirement(RequirementId::generate());
    }

    public function testReconstituteRestoresRequirementIdsWithoutValidation(): void
    {
        $createdAt = new DateTimeImmutable('2025-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2025-02-01 10:00:00');
        $deletedAt = new DateTimeImmutable('2025-03-01 10:00:00');
        $first = RequirementId::generate();
        $second = RequirementId::generate();

        $job = Job::reconstitute(
            $this->jobId,
            'Engineer',
            'IT',
            'Backend',
            null,
            'Desc',
            $createdAt,
            $updatedAt,
            4,
            $deletedAt,
            [$first, $second],
        );

        $this->assertSame($this->jobId, $job->id());
        $this->assertEquals('Engineer', $job->title());
        $this->assertEquals('IT', $job->category());
        $this->assertEquals('Backend', $job->subCategory());
        $this->assertEquals('Desc', $job->description());
        $this->assertSame($createdAt, $job->createdAt());
        $this->assertSame($updatedAt, $job->updatedAt());
        $this->assertSame($deletedAt, $job->deletedAt());
        $this->assertEquals(4, $job->version());
        $this->assertSame([$first, $second], $job->requirementIds());
    }
}
