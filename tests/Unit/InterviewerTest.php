<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Entities\Interviewer;
use App\Domain\ValueObjects\Contacts;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InterviewerTest extends TestCase
{
    public function testCannotCreateWithoutFullName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Interviewer('id', 'employer', '', null, null, new Contacts());
    }

    public function testInterviewerBelongsToEmployer(): void
    {
        $interviewer = new Interviewer('i1', 'e1', 'Ivan Ivanov', 'CTO', 'ivan@example.com', new Contacts('ivan@example.com', '+70000000000'));
        $this->assertEquals('e1', $interviewer->employerId);
    }

    public function testAssignUnassignVacancyUpdatesListAndTimestamp(): void
    {
        $interviewer = new Interviewer('i2', 'e2', 'Ivan', null, null, new Contacts());
        $before = $interviewer->updatedAt->getTimestamp();
        $interviewer->assignToVacancy('v1');
        $this->assertContains('v1', $interviewer->vacancyIds());
        $this->assertGreaterThanOrEqual($before, $interviewer->updatedAt->getTimestamp());

        $interviewer->unassignFromVacancy('v1');
        $this->assertNotContains('v1', $interviewer->vacancyIds());
    }
}
