<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Interviewer;
use App\Domain\Exceptions\ValidationException\InterviewerFullNameEmptyException;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\InterviewerContacts;
use Override;
use PHPUnit\Framework\TestCase;

final class InterviewerTest extends TestCase
{
    private InterviewerId $interviewerId;

    #[Override]
    protected function setUp(): void
    {
        $this->interviewerId = InterviewerId::generate();
    }

    public function testCreateValid(): void
    {
        $interviewer = Interviewer::create(
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
    }

    public function testCreateEmptyFullNameThrows(): void
    {
        $this->expectException(InterviewerFullNameEmptyException::class);
        Interviewer::create($this->interviewerId, '');
    }
}
