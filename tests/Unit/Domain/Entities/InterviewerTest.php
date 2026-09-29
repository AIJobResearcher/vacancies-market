<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Interviewer;
use App\Domain\Exceptions\ValidationException\InterviewerFullNameEmptyException;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\InterviewerContacts;
use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InterviewerTest extends TestCase
{
    private InterviewerId $interviewerId;

    #[Override]
    protected function setUp(): void
    {
        $this->interviewerId = InterviewerId::generate();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function emptyFullNameProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
        ];
    }

    public function testCreateValid(): void
    {
        $interviewer = Interviewer::createInterviewer(
            $this->interviewerId,
            'John Doe',
            'Manager',
            InterviewerContacts::fromArray([
                ['type' => 'profile_urls', 'value' => 'https://linkedin.com/in/john'],
                ['type' => 'email', 'value' => 'john@example.test'],
            ]),
            'https://avatar.test/john.png',
            'corr-123'
        );

        $this->assertEquals('John Doe', $interviewer->fullName());
        $this->assertEquals('Manager', $interviewer->position());
        $this->assertCount(2, $interviewer->contacts()?->items() ?? []);
        $this->assertEquals('https://avatar.test/john.png', $interviewer->avatarUrl());
        $this->assertTrue($interviewer->isActive());
        $this->assertEquals(1, $interviewer->version());
        $this->assertNull($interviewer->deletedAt());
        $this->assertEquals($this->interviewerId, $interviewer->id());
        $this->assertSame($interviewer->createdAt(), $interviewer->updatedAt());
    }

    public function testCreateWithoutOptionalFieldsDefaultsToNull(): void
    {
        $interviewer = Interviewer::createInterviewer($this->interviewerId, 'John Doe');

        $this->assertNull($interviewer->position());
        $this->assertNull($interviewer->contacts());
        $this->assertNull($interviewer->avatarUrl());
        $this->assertEquals(1, $interviewer->version());
    }

    public function testCreateTrimsFullName(): void
    {
        $interviewer = Interviewer::createInterviewer($this->interviewerId, '  John Doe  ');

        $this->assertEquals('John Doe', $interviewer->fullName());
    }

    #[DataProvider('emptyFullNameProvider')]
    public function testCreateEmptyFullNameThrows(string $fullName): void
    {
        $this->expectException(InterviewerFullNameEmptyException::class);
        Interviewer::createInterviewer($this->interviewerId, $fullName);
    }

    public function testUpdateInterviewerChangesProvidedFields(): void
    {
        $interviewer = Interviewer::createInterviewer(
            $this->interviewerId,
            'John Doe',
            'Manager',
            InterviewerContacts::fromArray([['type' => 'email', 'value' => 'john@example.test']]),
            'https://avatar.test/john.png',
        );
        $contacts = InterviewerContacts::fromArray([['type' => 'phone', 'value' => '+123456789']]);

        $interviewer->updateInterviewer('Jane Doe', 'Lead', $contacts, 'https://avatar.test/jane.png');

        $this->assertEquals('Jane Doe', $interviewer->fullName());
        $this->assertEquals('Lead', $interviewer->position());
        $this->assertSame($contacts, $interviewer->contacts());
        $this->assertEquals('https://avatar.test/jane.png', $interviewer->avatarUrl());
    }

    public function testUpdateInterviewerKeepsOmittedFields(): void
    {
        $contacts = InterviewerContacts::fromArray([['type' => 'email', 'value' => 'john@example.test']]);
        $interviewer = Interviewer::createInterviewer(
            $this->interviewerId,
            'John Doe',
            'Manager',
            $contacts,
            'https://avatar.test/john.png',
        );

        $interviewer->updateInterviewer(fullName: 'Jane Doe');

        $this->assertEquals('Jane Doe', $interviewer->fullName());
        $this->assertEquals('Manager', $interviewer->position());
        $this->assertSame($contacts, $interviewer->contacts());
        $this->assertEquals('https://avatar.test/john.png', $interviewer->avatarUrl());
    }

    public function testUpdateInterviewerBumpsVersionAndRefreshesUpdatedAt(): void
    {
        $interviewer = Interviewer::createInterviewer($this->interviewerId, 'John Doe');
        $updatedAt = $interviewer->updatedAt();

        $interviewer->updateInterviewer('Jane Doe');

        $this->assertEquals(2, $interviewer->version());
        $this->assertNotSame($updatedAt, $interviewer->updatedAt());
    }

    public function testUpdateInterviewerTrimsFullName(): void
    {
        $interviewer = Interviewer::createInterviewer($this->interviewerId, 'John Doe');
        $interviewer->updateInterviewer('  Jane Doe  ');

        $this->assertEquals('Jane Doe', $interviewer->fullName());
    }

    #[DataProvider('emptyFullNameProvider')]
    public function testUpdateInterviewerWithEmptyFullNameThrows(string $fullName): void
    {
        $interviewer = Interviewer::createInterviewer($this->interviewerId, 'John Doe');
        $this->expectException(InterviewerFullNameEmptyException::class);
        $interviewer->updateInterviewer($fullName);
    }

    public function testSoftDeleteSetsDeletedAtAndBumpsVersion(): void
    {
        $interviewer = Interviewer::createInterviewer($this->interviewerId, 'John Doe');

        $interviewer->softDelete();

        $this->assertNotNull($interviewer->deletedAt());
        $this->assertFalse($interviewer->isActive());
        $this->assertEquals(2, $interviewer->version());
    }

    public function testReconstituteRestoresState(): void
    {
        $createdAt = new DateTimeImmutable('2025-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2025-02-01 10:00:00');
        $deletedAt = new DateTimeImmutable('2025-03-01 10:00:00');
        $contacts = InterviewerContacts::fromArray([['type' => 'email', 'value' => 'john@example.test']]);

        $interviewer = Interviewer::reconstitute(
            $this->interviewerId,
            'John Doe',
            'Manager',
            $contacts,
            'https://avatar.test/john.png',
            $createdAt,
            $updatedAt,
            3,
            $deletedAt,
        );

        $this->assertSame($this->interviewerId, $interviewer->id());
        $this->assertEquals('John Doe', $interviewer->fullName());
        $this->assertEquals('Manager', $interviewer->position());
        $this->assertSame($contacts, $interviewer->contacts());
        $this->assertEquals('https://avatar.test/john.png', $interviewer->avatarUrl());
        $this->assertSame($createdAt, $interviewer->createdAt());
        $this->assertSame($updatedAt, $interviewer->updatedAt());
        $this->assertSame($deletedAt, $interviewer->deletedAt());
        $this->assertFalse($interviewer->isActive());
        $this->assertEquals(3, $interviewer->version());
    }
}
