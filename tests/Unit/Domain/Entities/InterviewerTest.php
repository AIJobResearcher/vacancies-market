<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Interviewer;
use App\Domain\Entities\Vacancy;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\Exceptions\OwnershipException\InterviewerVacancyEmployerMismatchException;
use App\Domain\Exceptions\StateConflictException\InterviewerAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\InterviewerIsNotActiveException;
use App\Domain\Exceptions\StateConflictException\NoActiveAssignmentException;
use App\Domain\Exceptions\ValidationException\InterviewerFullNameEmptyException;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\ExternalUrls;
use App\Domain\ValueObjects\Salary;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InterviewerTest extends TestCase
{
    private EmployerId $employerId;

    private InterviewerId $interviewerId;

    protected function setUp(): void
    {
        $this->employerId = EmployerId::generate();
        $this->interviewerId = InterviewerId::generate();
    }

    /**
     * @return array<string, array{
     *     0: string|null,
     *     1: string|null,
     *     2: array<string, string>|null
     * }>
     */
    public static function updateProfileProvider(): array
    {
        return [
            'all fields' => ['Jane Doe', 'Senior Manager', ['twitter' => 'https://twitter.com/jane']],
            'only name' => ['Jane Doe', null, null],
            'only position' => [null, 'Lead', null],
            'only urls' => [null, null, ['linkedin' => 'https://linkedin.com/in/jane']],
        ];
    }

    public function testCreateValid(): void
    {
        $interviewer = Interviewer::create(
            $this->interviewerId,
            $this->employerId,
            'John Doe',
            'Manager',
            ['linkedin' => 'https://linkedin.com/in/john'],
            'corr-123'
        );

        $this->assertEquals('John Doe', $interviewer->fullName());
        $this->assertEquals('Manager', $interviewer->position());
        $this->assertTrue($interviewer->isActive());
        $this->assertEquals(1, $interviewer->version());
        $this->assertNull($interviewer->deletedAt());
        $this->assertEquals($this->interviewerId, $interviewer->id());
        $this->assertEquals($this->employerId, $interviewer->employerId());
        $this->assertInstanceOf(DateTimeImmutable::class, $interviewer->createdAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $interviewer->updatedAt());
    }

    public function testCreateEmptyFullNameThrows(): void
    {
        $this->expectException(InterviewerFullNameEmptyException::class);
        Interviewer::create($this->interviewerId, $this->employerId, '');
    }

    public function testAssignToVacancySuccess(): void
    {
        $interviewer = Interviewer::create($this->interviewerId, $this->employerId, 'John Doe');
        $vacancy = $this->createVacancy($this->employerId);
        $oldVersion = $interviewer->version();

        $interviewer->assignToVacancy($vacancy);
        $assignments = $interviewer->getVacancyAssignments();
        $this->assertCount(1, $assignments);
        $this->assertTrue($assignments[0]->isActive());
        $this->assertEquals($oldVersion + 1, $interviewer->version());
    }

    public function testAssignToVacancyDifferentEmployerThrows(): void
    {
        $interviewer = Interviewer::create($this->interviewerId, $this->employerId, 'John Doe');
        $otherEmployerId = EmployerId::generate();
        $vacancy = $this->createVacancy($otherEmployerId);
        $this->expectException(InterviewerVacancyEmployerMismatchException::class);
        $interviewer->assignToVacancy($vacancy);
    }

    public function testAssignToVacancyWhenInactiveThrows(): void
    {
        $interviewer = Interviewer::create($this->interviewerId, $this->employerId, 'John Doe');
        $interviewer->softDelete();
        $vacancy = $this->createVacancy($this->employerId);
        $this->expectException(InterviewerIsNotActiveException::class);
        $interviewer->assignToVacancy($vacancy);
    }

    public function testAssignDuplicateThrows(): void
    {
        $interviewer = Interviewer::create($this->interviewerId, $this->employerId, 'John Doe');
        $vacancy = $this->createVacancy($this->employerId);
        $interviewer->assignToVacancy($vacancy);
        $this->expectException(InterviewerAlreadyAssignedException::class);
        $interviewer->assignToVacancy($vacancy);
    }

    public function testUnassignSuccess(): void
    {
        $interviewer = Interviewer::create($this->interviewerId, $this->employerId, 'John Doe');
        $vacancy = $this->createVacancy($this->employerId);
        $interviewer->assignToVacancy($vacancy);
        $oldVersion = $interviewer->version();

        $interviewer->unassignFromVacancy($vacancy);
        $assignments = $interviewer->getVacancyAssignments();
        $this->assertCount(1, $assignments);
        $this->assertFalse($assignments[0]->isActive());
        $this->assertEquals($oldVersion + 1, $interviewer->version());
    }

    public function testUnassignWithoutActiveAssignmentThrows(): void
    {
        $interviewer = Interviewer::create($this->interviewerId, $this->employerId, 'John Doe');
        $vacancy = $this->createVacancy($this->employerId);
        $this->expectException(NoActiveAssignmentException::class);
        $interviewer->unassignFromVacancy($vacancy);
    }

    public function testReassignAfterUnassignSucceeds(): void
    {
        $interviewer = Interviewer::create($this->interviewerId, $this->employerId, 'John Doe');
        $vacancy = $this->createVacancy($this->employerId);
        $interviewer->assignToVacancy($vacancy);
        $interviewer->unassignFromVacancy($vacancy);

        $interviewer->assignToVacancy($vacancy);
        $assignments = $interviewer->getVacancyAssignments();
        $this->assertCount(2, $assignments);
        $this->assertTrue($assignments[1]->isActive());
    }

    #[DataProvider('updateProfileProvider')]
    public function testUpdateProfile(?string $fullName, ?string $position, ?array $profileUrls): void
    {
        $interviewer = Interviewer::create($this->interviewerId, $this->employerId, 'John Doe');
        $oldVersion = $interviewer->version();

        $interviewer->updateProfile($fullName, $position, $profileUrls);

        if ($fullName !== null) {
            $this->assertEquals($fullName, $interviewer->fullName());
        }
        if ($position !== null) {
            $this->assertEquals($position, $interviewer->position());
        }
        if ($profileUrls !== null) {
            $this->assertEquals($profileUrls, $interviewer->profileUrls());
        }
        $this->assertEquals($oldVersion + 1, $interviewer->version());
    }

    public function testUpdateProfileWithEmptyFullNameThrows(): void
    {
        $interviewer = Interviewer::create($this->interviewerId, $this->employerId, 'John Doe');
        $this->expectException(InterviewerFullNameEmptyException::class);
        $interviewer->updateProfile('');
    }

    public function testSoftDelete(): void
    {
        $interviewer = Interviewer::create($this->interviewerId, $this->employerId, 'John Doe');
        $oldVersion = $interviewer->version();

        $interviewer->softDelete();
        $this->assertFalse($interviewer->isActive());
        $this->assertNotNull($interviewer->deletedAt());
        $this->assertEquals($oldVersion + 1, $interviewer->version());
    }

    private function createVacancy(EmployerId $employerId): Vacancy
    {
        return Vacancy::create(
            VacancyId::generate(),
            $employerId,
            'Developer',
            'Desc',
            new Salary(1000, 2000),
            'USA',
            'NYC',
            EmploymentTypeEnum::FULL_TIME,
            WorkplaceEnum::REMOTE,
            new DateTimeImmutable(),
            new ExternalUrls(['https://example.com']),
            null,
            null
        );
    }
}
