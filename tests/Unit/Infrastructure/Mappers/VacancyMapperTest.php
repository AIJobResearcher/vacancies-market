<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Mappers;

use App\Domain\Entities\Vacancy;
use App\Domain\Entities\VacancyJobAssignment;
use App\Domain\Entities\VacancyRequirementAssignment;
use App\Domain\Entities\VacancySource;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\EntityIds\VacancyJobAssignmentId;
use App\Domain\ValueObjects\EntityIds\VacancyRequirementAssignmentId;
use App\Domain\ValueObjects\EntityIds\VacancySourceId;
use App\Domain\ValueObjects\ExternalUrls;
use App\Domain\ValueObjects\Salary;
use App\Infrastructure\Eloquents\Mappers\VacancyMapper;
use App\Infrastructure\Eloquents\Models\VacancyJobAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use App\Infrastructure\Eloquents\Models\VacancyRequirementAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancySourceModel;
use DateTimeImmutable;
use Tests\TestCase;

final class VacancyMapperTest extends TestCase
{
    public function testToEloquentMapsDomainToModel(): void
    {
        $vacancy = $this->domainVacancy();

        $model = (new VacancyMapper())->toEloquent($vacancy);

        $this->assertSame($vacancy->id()->value(), $model->id);
        $this->assertSame($vacancy->employerId()->value(), $model->employer_id);
        $this->assertSame('Closed Role', $model->title);
        $this->assertSame('closed', $model->status);
        $this->assertSame('full-time', $model->employment_type);
        $this->assertSame('remote', $model->workplace);
        $this->assertSame(7, $model->version);
        $this->assertSame(1000, $model->salary_min);
        $this->assertSame(2000, $model->salary_max);
        $this->assertSame('USD', $model->salary_currency);
        $this->assertSame(['https://example.com/vacancy'], $model->external_urls);
    }

    public function testToDomainRestoresChildrenFromLoadedRelations(): void
    {
        $model = $this->model();
        $model->setRelation('requirementAssignments', collect([$this->requirementModel($model)]));
        $model->setRelation('jobAssignments', collect([$this->jobModel($model)]));
        $model->setRelation('sources', collect([$this->sourceModel($model)]));

        $vacancy = (new VacancyMapper())->toDomain($model);

        $this->assertSame($model->id, $vacancy->id()->value());
        $this->assertEquals(VacancyStatusEnum::CLOSED, VacancyStatusEnum::from($vacancy->status()));
        $this->assertSame(7, $vacancy->version());
        $this->assertSame('USA', $vacancy->country());
        $this->assertSame('NYC', $vacancy->city());
        $this->assertEquals(new Salary(1000, 2000, 'USD'), $vacancy->salary());
        $this->assertEquals(new ExternalUrls(['https://example.com/vacancy']), $vacancy->externalUrls());
        $this->assertCount(1, $vacancy->requirementAssignments());
        $this->assertCount(1, $vacancy->jobAssignments());
        $this->assertCount(1, $vacancy->sources());

        $req = $vacancy->requirementAssignments()[0];
        $this->assertSame('33333333-3333-3333-3333-333333333333', $req->id()->value());
        $this->assertSame('44444444-4444-4444-4444-444444444444', $req->getRequirementId()->value());
        $this->assertSame(3, $req->version());

        $job = $vacancy->jobAssignments()[0];
        $this->assertFalse($job->isActive());
        $this->assertSame(5, $job->version());

        $source = $vacancy->sources()[0];
        $this->assertSame('linkedin', $source->sourceKey());
        $this->assertTrue($source->isPrimary());
    }

    private function domainVacancy(): Vacancy
    {
        $vacancyId = VacancyId::fromString('22222222-2222-2222-2222-222222222222');

        return Vacancy::reconstitute(
            $vacancyId,
            EmployerId::fromString('11111111-1111-1111-1111-111111111111'),
            'Closed Role',
            'Description',
            new Salary(1000, 2000, 'USD'),
            VacancyStatusEnum::CLOSED,
            'USA',
            'NYC',
            EmploymentTypeEnum::FULL_TIME,
            WorkplaceEnum::REMOTE,
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-02-01 10:00:00'),
            new DateTimeImmutable('2025-03-01 10:00:00'),
            7,
            new ExternalUrls(['https://example.com/vacancy']),
            null,
            [$this->requirement($vacancyId)],
            [$this->jobAssignment($vacancyId)],
            [$this->source($vacancyId)]
        );
    }

    private function requirement(VacancyId $vacancyId): VacancyRequirementAssignment
    {
        return new VacancyRequirementAssignment(
            VacancyRequirementAssignmentId::fromString('33333333-3333-3333-3333-333333333333'),
            $vacancyId,
            RequirementId::fromString('44444444-4444-4444-4444-444444444444'),
            new DateTimeImmutable('2025-01-02 10:00:00'),
            3
        );
    }

    private function jobAssignment(VacancyId $vacancyId): VacancyJobAssignment
    {
        return new VacancyJobAssignment(
            VacancyJobAssignmentId::fromString('55555555-5555-5555-5555-555555555555'),
            $vacancyId,
            JobId::fromString('66666666-6666-6666-6666-666666666666'),
            new DateTimeImmutable('2025-01-03 10:00:00'),
            80,
            4,
            new DateTimeImmutable('2025-04-01 10:00:00')
        );
    }

    private function source(VacancyId $vacancyId): VacancySource
    {
        return new VacancySource(
            VacancySourceId::fromString('77777777-7777-7777-7777-777777777777'),
            $vacancyId,
            'linkedin',
            'ext123',
            'https://linkedin.com/123',
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-01-02 10:00:00'),
            null,
            true
        );
    }

    private function model(): VacancyModel
    {
        $model = new VacancyModel();
        $model->id = '22222222-2222-2222-2222-222222222222';
        $model->employer_id = '11111111-1111-1111-1111-111111111111';
        $model->title = 'Closed Role';
        $model->description = 'Description';
        $model->salary_min = 1000;
        $model->salary_max = 2000;
        $model->salary_currency = 'USD';
        $model->status = 'closed';
        $model->country = 'USA';
        $model->city = 'NYC';
        $model->employment_type = 'full-time';
        $model->workplace = 'remote';
        $model->posted_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $model->closed_at = new DateTimeImmutable('2025-03-01 10:00:00');
        $model->created_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $model->updated_at = new DateTimeImmutable('2025-02-01 10:00:00');
        $model->version = 7;
        $model->external_urls = ['https://example.com/vacancy'];
        $model->internal_url = null;

        return $model;
    }

    private function requirementModel(VacancyModel $model): VacancyRequirementAssignmentModel
    {
        $req = new VacancyRequirementAssignmentModel();
        $req->id = '33333333-3333-3333-3333-333333333333';
        $req->vacancy_id = $model->id;
        $req->requirement_id = '44444444-4444-4444-4444-444444444444';
        $req->assigned_at = new DateTimeImmutable('2025-01-02 10:00:00');
        $req->version = 3;

        return $req;
    }

    private function jobModel(VacancyModel $model): VacancyJobAssignmentModel
    {
        $job = new VacancyJobAssignmentModel();
        $job->id = '55555555-5555-5555-5555-555555555555';
        $job->vacancy_id = $model->id;
        $job->job_id = '66666666-6666-6666-6666-666666666666';
        $job->assigned_at = new DateTimeImmutable('2025-01-03 10:00:00');
        $job->unassigned_at = new DateTimeImmutable('2025-04-01 10:00:00');
        $job->relevance_score = 80;
        $job->version = 5;

        return $job;
    }

    private function sourceModel(VacancyModel $model): VacancySourceModel
    {
        $source = new VacancySourceModel();
        $source->id = '77777777-7777-7777-7777-777777777777';
        $source->vacancy_id = $model->id;
        $source->source_key = 'linkedin';
        $source->external_vacancy_id = 'ext123';
        $source->external_url = 'https://linkedin.com/123';
        $source->first_seen_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $source->last_seen_at = new DateTimeImmutable('2025-01-02 10:00:00');
        $source->closed_at = null;
        $source->is_primary = true;

        return $source;
    }
}
