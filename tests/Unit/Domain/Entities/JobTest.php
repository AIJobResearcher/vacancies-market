<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Job;
use App\Domain\Exceptions\StateConflictException\RequirementAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\RequirementNotAssignedException;
use App\Domain\Exceptions\ValidationException\JobTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    private JobId $jobId;

    private RequirementId $reqId;

    protected function setUp(): void
    {
        $this->jobId = JobId::generate();
        $this->reqId = RequirementId::generate();
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: string|null,
     *     2: string|null,
     *     3: JobId|null,
     *     4: string|null
     * }>
     */
    public static function validCreateProvider(): array
    {
        return [
            'all fields' => ['Software Engineer', 'IT', 'Backend', null, 'Description'],
            'minimal' => ['Engineer', null, null, null, null],
            'with parent' => ['Junior', 'IT', null, JobId::generate(), null],
        ];
    }

    #[DataProvider('validCreateProvider')]
    public function testCreateValid(
        string $title,
        ?string $category,
        ?string $subCategory,
        ?JobId $parent,
        ?string $desc
    ): void {
        $job = Job::create($this->jobId, $title, $category, $subCategory, $parent, $desc, 'corr-123');
        $this->assertEquals($title, $job->title());
        $this->assertEquals($category, $job->category());
        $this->assertEquals($subCategory, $job->subCategory());
        $this->assertEquals($parent?->value(), $job->parentJobId()?->value());
        $this->assertEquals(1, $job->version());
        $this->assertNull($job->deletedAt());
    }

    public function testCreateEmptyTitleThrows(): void
    {
        $this->expectException(JobTitleEmptyException::class);
        Job::create($this->jobId, '');
    }

    public function testAddRequirement(): void
    {
        $job = Job::create($this->jobId, 'Engineer');
        $oldVersion = $job->version();

        $job->addRequirement($this->reqId);
        $this->assertEquals($oldVersion + 1, $job->version());
    }

    public function testAddDuplicateRequirementThrows(): void
    {
        $job = Job::create($this->jobId, 'Engineer');
        $job->addRequirement($this->reqId);
        $this->expectException(RequirementAlreadyAssignedException::class);
        $job->addRequirement($this->reqId);
    }

    public function testRemoveRequirement(): void
    {
        $job = Job::create($this->jobId, 'Engineer');
        $job->addRequirement($this->reqId);
        $oldVersion = $job->version();

        $job->removeRequirement($this->reqId);
        $this->assertEquals($oldVersion + 1, $job->version());
        // adding again works
        $job->addRequirement($this->reqId);
    }

    public function testRemoveNonExistentRequirementThrows(): void
    {
        $job = Job::create($this->jobId, 'Engineer');
        $this->expectException(RequirementNotAssignedException::class);
        $job->removeRequirement(RequirementId::generate());
    }

    public function testSoftDelete(): void
    {
        $job = Job::create($this->jobId, 'Engineer');
        $oldVersion = $job->version();

        $job->softDelete();
        $this->assertNotNull($job->deletedAt());
        $this->assertEquals($oldVersion + 1, $job->version());
    }
}
