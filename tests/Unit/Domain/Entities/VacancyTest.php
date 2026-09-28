<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Source;
use App\Domain\Entities\Vacancy;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\Exceptions\ValidationException\SalaryMaxLessThanMinException;
use App\Domain\Exceptions\ValidationException\SalaryMaxNegativeException;
use App\Domain\Exceptions\ValidationException\SalaryMinNegativeException;
use App\Domain\Exceptions\ValidationException\VacancyTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\SourceId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
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
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('invalidCreateProvider')]
    public function testCreateInvalid(string $title, string $exceptionClass): void
    {
        $this->expectException($exceptionClass);
        Vacancy::create($this->vacancyId, $this->employerId, $title);
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('invalidSalaryProvider')]
    public function testCreateInvalidSalary(int $minSalary, ?int $maxSalary, string $exceptionClass): void
    {
        $this->expectException($exceptionClass);
        Vacancy::create($this->vacancyId, $this->employerId, 'Software Engineer', $minSalary, $maxSalary);
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
        $source = new Source(
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

    private function createVacancy(): Vacancy
    {
        return Vacancy::create(
            $this->vacancyId,
            $this->employerId,
            'Software Engineer',
            1000,
            2000,
            [EmploymentTypeEnum::FULL_TIME],
            [WorkplaceEnum::REMOTE],
            [804],
        );
    }
}
