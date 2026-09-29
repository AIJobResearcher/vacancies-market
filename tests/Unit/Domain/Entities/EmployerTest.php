<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Employer;
use App\Domain\Entities\Interviewer;
use App\Domain\Entities\Source;
use App\Domain\Entities\Vacancy;
use App\Domain\Exceptions\OwnershipException\VacancyBelongsToDifferentEmployerException;
use App\Domain\Exceptions\StateConflictException\VacancyNotClosedException;
use App\Domain\Exceptions\ValidationException\EmployerTitleEmptyException;
use App\Domain\ValueObjects\EmployerContacts;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Domain\ValueObjects\EntityIds\SourceId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmployerTest extends TestCase
{
    private EmployerId $employerId;

    #[Override]
    protected function setUp(): void
    {
        $this->employerId = EmployerId::generate();
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

    public function testCreateValid(): void
    {
        $employer = $this->createEmployer();

        $this->assertSame($this->employerId, $employer->id());
        $this->assertEquals('TechCorp', $employer->title());
        $this->assertEquals('Description', $employer->description());
        $this->assertCount(3, $employer->contacts()?->items() ?? []);
        $this->assertEquals('https://logo.test/logo.png', $employer->logoUrl());
        $this->assertEquals([804, 703448], $employer->locationIds());
        $this->assertEquals(1, $employer->version());
        $this->assertSame($employer->createdAt(), $employer->updatedAt());
    }

    public function testCreateTrimsTitle(): void
    {
        $employer = Employer::createEmployer($this->employerId, '  TechCorp  ');

        $this->assertEquals('TechCorp', $employer->title());
    }

    #[DataProvider('emptyTitleProvider')]
    public function testCreateEmptyTitleThrows(string $title): void
    {
        $this->expectException(EmployerTitleEmptyException::class);
        Employer::createEmployer($this->employerId, $title);
    }

    public function testUpdateDetailsChangesProvidedFields(): void
    {
        $employer = $this->createEmployer();
        $contacts = EmployerContacts::fromArray([['type' => 'phone', 'value' => '+123456789']]);

        $employer->updateDetails('NewCorp', 'New desc', $contacts, 'https://logo.test/new.png', [1, 2]);

        $this->assertEquals('NewCorp', $employer->title());
        $this->assertEquals('New desc', $employer->description());
        $this->assertSame($contacts, $employer->contacts());
        $this->assertEquals('https://logo.test/new.png', $employer->logoUrl());
        $this->assertEquals([1, 2], $employer->locationIds());
    }

    public function testUpdateDetailsKeepsOmittedFields(): void
    {
        $employer = $this->createEmployer();
        $contacts = $employer->contacts();

        $employer->updateDetails(title: 'NewCorp');

        $this->assertEquals('NewCorp', $employer->title());
        $this->assertEquals('Description', $employer->description());
        $this->assertSame($contacts, $employer->contacts());
        $this->assertEquals('https://logo.test/logo.png', $employer->logoUrl());
        $this->assertEquals([804, 703448], $employer->locationIds());
    }

    public function testUpdateDetailsBumpsVersionAndRefreshesUpdatedAt(): void
    {
        $employer = $this->createEmployer();
        $updatedAt = $employer->updatedAt();

        $employer->updateDetails('NewCorp');

        $this->assertEquals(2, $employer->version());
        $this->assertNotSame($updatedAt, $employer->updatedAt());
    }

    public function testUpdateDetailsTrimsTitle(): void
    {
        $employer = $this->createEmployer();
        $employer->updateDetails('  NewCorp  ');

        $this->assertEquals('NewCorp', $employer->title());
    }

    #[DataProvider('emptyTitleProvider')]
    public function testUpdateDetailsWithEmptyTitleThrows(string $title): void
    {
        $employer = $this->createEmployer();

        $this->expectException(EmployerTitleEmptyException::class);
        $employer->updateDetails($title);
    }

    public function testAddVacancyAcceptsOwnVacancy(): void
    {
        $employer = $this->createEmployer();

        $employer->addVacancy($this->vacancy($this->employerId));

        $this->assertEquals(1, $employer->version());
    }

    public function testAddVacancyForDifferentEmployerThrows(): void
    {
        $employer = $this->createEmployer();

        $this->expectException(VacancyBelongsToDifferentEmployerException::class);
        $employer->addVacancy($this->vacancy(EmployerId::generate()));
    }

    public function testRemoveVacancyOpenThrows(): void
    {
        $employer = $this->createEmployer();

        $this->expectException(VacancyNotClosedException::class);
        $employer->removeVacancy($this->vacancy($this->employerId));
    }

    public function testRemoveVacancyClosedPasses(): void
    {
        $employer = $this->createEmployer();
        $vacancy = $this->vacancy($this->employerId);
        $vacancy->closeVacancy();

        $employer->removeVacancy($vacancy);

        $this->assertEquals('closed', $vacancy->status());
        $this->assertEquals(1, $employer->version());
    }

    public function testAddInterviewerIsNoOp(): void
    {
        $employer = $this->createEmployer();
        $interviewer = Interviewer::createInterviewer(InterviewerId::generate(), 'John Doe');

        $employer->addInterviewer($interviewer);

        $this->assertEquals(1, $employer->version());
        $this->assertTrue($interviewer->isActive());
    }

    public function testRemoveInterviewerIsNoOp(): void
    {
        $employer = $this->createEmployer();
        $interviewer = Interviewer::createInterviewer(InterviewerId::generate(), 'John Doe');

        $employer->removeInterviewer($interviewer);

        $this->assertEquals(1, $employer->version());
        $this->assertTrue($interviewer->isActive());
    }

    public function testReconstituteRestoresState(): void
    {
        $createdAt = new DateTimeImmutable('2025-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2025-02-01 10:00:00');
        $contacts = EmployerContacts::fromArray([['type' => 'email', 'value' => 'info@techcorp.test']]);

        $employer = Employer::reconstitute(
            $this->employerId,
            'TechCorp',
            'Description',
            $contacts,
            'https://logo.test/logo.png',
            [804],
            $createdAt,
            $updatedAt,
            5,
        );

        $this->assertSame($this->employerId, $employer->id());
        $this->assertEquals('TechCorp', $employer->title());
        $this->assertEquals('Description', $employer->description());
        $this->assertSame($contacts, $employer->contacts());
        $this->assertEquals('https://logo.test/logo.png', $employer->logoUrl());
        $this->assertEquals([804], $employer->locationIds());
        $this->assertSame($createdAt, $employer->createdAt());
        $this->assertSame($updatedAt, $employer->updatedAt());
        $this->assertEquals(5, $employer->version());
    }

    public function testToArrayShape(): void
    {
        $employer = $this->createEmployer();

        $this->assertSame([
            'id' => $this->employerId->value(),
            'title' => 'TechCorp',
            'description' => 'Description',
            'contacts' => [
                ['type' => 'website', 'value' => 'https://techcorp.test'],
                ['type' => 'email', 'value' => 'info@techcorp.test'],
                ['type' => 'phone', 'value' => '+123456789'],
            ],
            'logo_url' => 'https://logo.test/logo.png',
            'location_ids' => [804, 703448],
            'created_at' => $employer->createdAt()->format(DATE_ATOM),
            'updated_at' => $employer->updatedAt()->format(DATE_ATOM),
            'version' => 1,
        ], $employer->toArray());
    }

    private function createEmployer(): Employer
    {
        return Employer::createEmployer(
            $this->employerId,
            'TechCorp',
            'Description',
            EmployerContacts::fromArray([
                ['type' => 'website', 'value' => 'https://techcorp.test'],
                ['type' => 'email', 'value' => 'info@techcorp.test'],
                ['type' => 'phone', 'value' => '+123456789'],
            ]),
            'https://logo.test/logo.png',
            [804, 703448],
        );
    }

    private function vacancy(EmployerId $employerId): Vacancy
    {
        $vacancyId = VacancyId::generate();
        $timestamp = new DateTimeImmutable('2025-01-01 10:00:00');

        return Vacancy::createVacancy(
            $vacancyId,
            $employerId,
            'Software Engineer',
            1000,
            2000,
            [],
            [],
            [804],
            [JobId::generate()],
            [
                Source::reconstitute(
                    SourceId::generate(),
                    $vacancyId,
                    PortalId::generate(),
                    null,
                    'https://linkedin.test/vacancies/1',
                    'Senior PHP',
                    $timestamp,
                    $timestamp,
                    $timestamp,
                ),
            ],
        );
    }
}
