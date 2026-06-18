<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Entities\Vacancy;
use App\Domain\Enums\VacancyStatus;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class VacancyTest extends TestCase
{
    public function testCannotCreateWithoutTitle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Vacancy('id', 'employer', '', 'desc', [], null, VacancyStatus::OPEN, null, null);
    }

    public function testCannotCreateWithoutEmployerId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Vacancy('id', '', 'Title', 'desc', [], null, VacancyStatus::OPEN, null, null);
    }

    public function testVersionIncrementsOnChangesAndReopen(): void
    {
        $vacancy = new Vacancy(
            'v1',
            'e1',
            'Title',
            'Desc',
            ['PHP'],
            null,
            VacancyStatus::OPEN,
            'RU',
            'Moscow'
        );

        $initialVersion = $vacancy->version;

        $vacancy->updateDescription('New desc');
        $this->assertGreaterThan($initialVersion, $vacancy->version);
        $verAfterDesc = $vacancy->version;

        $vacancy->updateRequirements(['PHP', 'Laravel']);
        $this->assertGreaterThan($verAfterDesc, $vacancy->version);
        $verAfterReq = $vacancy->version;

        $vacancy->close();
        $this->assertEquals(VacancyStatus::CLOSED, $vacancy->status);
        $this->assertGreaterThan($verAfterReq, $vacancy->version);

        $publishAt = new DateTimeImmutable('+' . 1 . ' day');
        $vacancy->reopen($publishAt);
        $this->assertEquals(VacancyStatus::OPEN, $vacancy->status);
        $this->assertEquals($publishAt->getTimestamp(), $vacancy->createdAt->getTimestamp());
    }
}
