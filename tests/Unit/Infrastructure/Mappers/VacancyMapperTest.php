<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Mappers;

use App\Domain\Entities\Content;
use App\Domain\Entities\Source;
use App\Domain\Entities\Vacancy;
use App\Domain\Enums\ContentTypeEnum;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\ValueObjects\EntityIds\ContentId;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\SourceId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Infrastructure\Eloquents\Mappers\ContentMapper;
use App\Infrastructure\Eloquents\Mappers\SourceMapper;
use App\Infrastructure\Eloquents\Mappers\VacancyMapper;
use App\Infrastructure\Eloquents\Models\ContentModel;
use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use App\Infrastructure\Eloquents\Models\SourceModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use DateTimeImmutable;
use Tests\TestCase;

final class VacancyMapperTest extends TestCase
{
    public function testToEloquentMapsDomainToModel(): void
    {
        $vacancy = $this->domainVacancy();

        $model = $this->mapper()->toEloquent($vacancy);

        $this->assertSame($vacancy->id()->value(), $model->id);
        $this->assertSame($vacancy->employerId()->value(), $model->employer_id);
        $this->assertSame('Closed Role', $model->title);
        $this->assertSame(1000, $model->min_salary);
        $this->assertSame(2000, $model->max_salary);
        $this->assertSame('closed', $model->status);
        $this->assertSame(['full-time'], $model->employment_types);
        $this->assertSame(['remote'], $model->workplaces);
        $this->assertSame([804], $model->researcher_location_ids);
        $this->assertNotNull($model->closed_at);
        $this->assertSame(7, $model->version);
    }

    public function testToDomainRestoresVacancyAndChildrenFromLoadedRelations(): void
    {
        $model = $this->model();
        $model->setRelation('requirements', collect([$this->requirementModel()]));
        $model->setRelation('jobs', collect([$this->jobRow()]));
        $model->setRelation('sources', collect([$this->sourceModel($model)]));

        $vacancy = $this->mapper()->toDomain($model);

        $this->assertSame($model->id, $vacancy->id()->value());
        $this->assertEquals(VacancyStatusEnum::CLOSED, VacancyStatusEnum::from($vacancy->status()));
        $this->assertSame(7, $vacancy->version());
        $this->assertSame(1000, $vacancy->minSalary());
        $this->assertSame(2000, $vacancy->maxSalary());
        $this->assertEquals([EmploymentTypeEnum::FULL_TIME], $vacancy->employmentTypes());
        $this->assertEquals([WorkplaceEnum::REMOTE], $vacancy->workplaces());
        $this->assertSame([804], $vacancy->researcherLocationIds());
        $this->assertCount(1, $vacancy->requirementIds());
        $this->assertCount(1, $vacancy->jobIds());
        $this->assertCount(1, $vacancy->sources());

        $this->assertSame('44444444-4444-4444-4444-444444444444', $vacancy->requirementIds()[0]->value());
        $this->assertSame('66666666-6666-6666-6666-666666666666', $vacancy->jobIds()[0]->value());

        $source = $vacancy->sources()[0];
        $this->assertSame('77777777-7777-7777-7777-777777777777', $source->id()->value());
        $this->assertSame('https://linkedin.com/123', $source->externalUrl());
        $this->assertSame('Senior PHP', $source->title());
        $this->assertCount(1, $source->contents());
        $this->assertEquals(ContentTypeEnum::DESCRIPTION, $source->contents()[0]->type());
        $this->assertSame('Body text', $source->contents()[0]->value());
    }

    private function mapper(): VacancyMapper
    {
        return new VacancyMapper(new SourceMapper(new ContentMapper()));
    }

    private function domainVacancy(): Vacancy
    {
        $vacancyId = VacancyId::fromString('22222222-2222-2222-2222-222222222222');

        return Vacancy::reconstitute(
            $vacancyId,
            EmployerId::fromString('11111111-1111-1111-1111-111111111111'),
            'Closed Role',
            1000,
            2000,
            VacancyStatusEnum::CLOSED,
            [EmploymentTypeEnum::FULL_TIME],
            [WorkplaceEnum::REMOTE],
            [804],
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-02-01 10:00:00'),
            new DateTimeImmutable('2025-03-01 10:00:00'),
            7,
            [RequirementId::fromString('44444444-4444-4444-4444-444444444444')],
            [JobId::fromString('66666666-6666-6666-6666-666666666666')],
            [$this->source($vacancyId)]
        );
    }

    private function source(VacancyId $vacancyId): Source
    {
        return Source::reconstitute(
            SourceId::fromString('77777777-7777-7777-7777-777777777777'),
            $vacancyId,
            PortalId::fromString('88888888-8888-8888-8888-888888888888'),
            'ext123',
            'https://linkedin.com/123',
            'Senior PHP',
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-01-02 10:00:00'),
            [
                Content::createContent(
                    ContentId::fromString('99999999-9999-9999-9999-999999999999'),
                    SourceId::fromString('77777777-7777-7777-7777-777777777777'),
                    ContentTypeEnum::DESCRIPTION,
                    'Body text'
                ),
            ]
        );
    }

    private function model(): VacancyModel
    {
        $model = new VacancyModel();
        $model->id = '22222222-2222-2222-2222-222222222222';
        $model->employer_id = '11111111-1111-1111-1111-111111111111';
        $model->title = 'Closed Role';
        $model->min_salary = 1000;
        $model->max_salary = 2000;
        $model->status = 'closed';
        $model->employment_types = ['full-time'];
        $model->workplaces = ['remote'];
        $model->researcher_location_ids = [804];
        $model->closed_at = new DateTimeImmutable('2025-03-01 10:00:00');
        $model->created_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $model->updated_at = new DateTimeImmutable('2025-02-01 10:00:00');
        $model->version = 7;

        return $model;
    }

    private function requirementModel(): RequirementModel
    {
        $req = new RequirementModel();
        $req->id = '44444444-4444-4444-4444-444444444444';

        return $req;
    }

    private function jobRow(): JobModel
    {
        $job = new JobModel();
        $job->id = '66666666-6666-6666-6666-666666666666';

        return $job;
    }

    private function sourceModel(VacancyModel $model): SourceModel
    {
        $source = new SourceModel();
        $source->id = '77777777-7777-7777-7777-777777777777';
        $source->vacancy_id = $model->id;
        $source->portal_id = '88888888-8888-8888-8888-888888888888';
        $source->external_vacancy_id = 'ext123';
        $source->external_url = 'https://linkedin.com/123';
        $source->title = 'Senior PHP';
        $source->posted_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $source->created_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $source->updated_at = new DateTimeImmutable('2025-01-02 10:00:00');
        $source->setRelation('contents', collect([$this->contentModel()]));

        return $source;
    }

    private function contentModel(): ContentModel
    {
        $content = new ContentModel();
        $content->id = '99999999-9999-9999-9999-999999999999';
        $content->source_id = '77777777-7777-7777-7777-777777777777';
        $content->type = 'description';
        $content->value = 'Body text';

        return $content;
    }
}
