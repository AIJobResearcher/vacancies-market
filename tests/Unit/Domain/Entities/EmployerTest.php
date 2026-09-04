<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Employer;
use App\Domain\Entities\Interviewer;
use App\Domain\Entities\Vacancy;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\Events\EmployerImportedEvent;
use App\Domain\Exceptions\OwnershipException\InterviewerBelongsToDifferentEmployerException;
use App\Domain\Exceptions\OwnershipException\VacancyBelongsToDifferentEmployerException;
use App\Domain\Exceptions\StateConflictException\VacancyNotClosedException;
use App\Domain\Exceptions\ValidationException\EmployerTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\ExternalUrls;
use App\Domain\ValueObjects\Salary;
use DateTimeImmutable;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmployerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private EmployerId $employerId;

    #[Override]
    protected function setUp(): void
    {
        $this->employerId = EmployerId::generate();
    }

    /**
     * @return array<string, array{
     *     0: string|null,
     *     1: string|null,
     *     2: string|null,
     *     3: string|null,
     *     4: string|null,
     *     5: string|null
     * }>
     */
    public static function updateDetailsProvider(): array
    {
        return [
            'all fields' => ['NewTitle', 'NewDesc', 'https://new.com', 'new@email.com', '+999', 'logo_new.png'],
            'only title' => ['OnlyTitle', null, null, null, null, null],
            'only description' => [null, 'OnlyDesc', null, null, null, null],
            'only website' => [null, null, 'https://only.com', null, null, null],
            'mixed' => [null, 'Desc', 'https://site.com', null, '+111', null],
        ];
    }

    public function test_create_valid(): void
    {
        $employer = Employer::create(
            $this->employerId,
            'TechCorp',
            'Description',
            'https://techcorp.com',
            'info@techcorp.com',
            '+123456789',
            'https://logo.com/logo.png',
            'corr-123'
        );

        $this->assertEquals('TechCorp', $employer->title());
        $this->assertEquals(1, $employer->version());
        $this->assertInstanceOf(DateTimeImmutable::class, $employer->createdAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $employer->updatedAt());

        $events = $employer->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(EmployerImportedEvent::class, $events[0]);
        $this->assertEquals('corr-123', $events[0]->correlationId);
        $this->assertEquals(1, $events[0]->eventVersion);
    }

    public function test_create_empty_title_throws(): void
    {
        $this->expectException(EmployerTitleEmptyException::class);
        Employer::create($this->employerId, '');
    }

    #[DataProvider('updateDetailsProvider')]
    public function test_update_details(
        ?string $title,
        ?string $desc,
        ?string $website,
        ?string $email,
        ?string $phone,
        ?string $logo
    ): void {
        $employer = Employer::create($this->employerId, 'OldTitle');
        $oldVersion = $employer->version();

        $employer->updateDetails($title, $desc, $website, $email, $phone, $logo);

        $this->assertEquals($title ?? 'OldTitle', $employer->title());
        $this->assertEquals($desc, $employer->description());
        $this->assertEquals($website, $employer->website());
        $this->assertEquals($email, $employer->email());
        $this->assertEquals($phone, $employer->phone());
        $this->assertEquals($logo, $employer->logoUrl());
        $this->assertEquals($oldVersion + 1, $employer->version());
    }

    public function test_update_details_with_empty_title_throws(): void
    {
        $employer = Employer::create($this->employerId, 'Title');
        $this->expectException(EmployerTitleEmptyException::class);
        $employer->updateDetails('');
    }

    public function test_add_vacancy_with_same_employer(): void
    {
        $this->expectNotToPerformAssertions();
        $employer = Employer::create($this->employerId, 'TechCorp');
        $vacancy = $this->createVacancy($this->employerId);
        $employer->addVacancy($vacancy);
    }

    public function test_add_vacancy_with_different_employer_throws(): void
    {
        $employer = Employer::create($this->employerId, 'TechCorp');
        $otherEmployerId = EmployerId::generate();
        $vacancy = $this->createVacancy($otherEmployerId);
        $this->expectException(VacancyBelongsToDifferentEmployerException::class);
        $employer->addVacancy($vacancy);
    }

    public function test_remove_vacancy_only_when_closed(): void
    {
        $employer = Employer::create($this->employerId, 'TechCorp');
        $vacancy = $this->createVacancy($this->employerId);
        $this->expectException(VacancyNotClosedException::class);
        $employer->removeVacancy($vacancy);
    }

    public function test_remove_vacancy_when_closed_succeeds(): void
    {
        $this->expectNotToPerformAssertions();
        $employer = Employer::create($this->employerId, 'TechCorp');
        $vacancy = $this->createVacancy($this->employerId);
        $vacancy->close();

        $employer->removeVacancy($vacancy);
    }

    public function test_remove_interviewer_succeeds(): void
    {
        $this->expectNotToPerformAssertions();
        $employer = Employer::create($this->employerId, 'TechCorp');
        $interviewer = $this->createInterviewer($this->employerId);

        $employer->removeInterviewer($interviewer);
    }

    public function test_add_interviewer_with_same_employer(): void
    {
        $this->expectNotToPerformAssertions();
        $employer = Employer::create($this->employerId, 'TechCorp');
        $interviewer = $this->createInterviewer($this->employerId);
        $employer->addInterviewer($interviewer);
    }

    public function test_add_interviewer_with_different_employer_throws(): void
    {
        $employer = Employer::create($this->employerId, 'TechCorp');
        $otherEmployerId = EmployerId::generate();
        $interviewer = $this->createInterviewer($otherEmployerId);
        $this->expectException(InterviewerBelongsToDifferentEmployerException::class);
        $employer->addInterviewer($interviewer);
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
            new DateTimeImmutable,
            new ExternalUrls(['https://example.com']),
            null,
            null
        );
    }

    private function createInterviewer(EmployerId $employerId): Interviewer
    {
        return Interviewer::create(
            InterviewerId::generate(),
            $employerId,
            'John Doe',
            'Manager',
            null,
            null
        );
    }
}
