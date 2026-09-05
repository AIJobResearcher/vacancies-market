<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Mappers;

use App\Domain\Entities\Job;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Infrastructure\Eloquents\Mappers\JobMapper;
use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\JobRequirementModel;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class JobMapperTest extends TestCase
{
    public function testToEloquentMapsDomainToModel(): void
    {
        $job = $this->domainJob();

        $model = (new JobMapper())->toEloquent($job);

        $this->assertSame($job->id()->value(), $model->id);
        $this->assertSame('Software Engineer', $model->title);
        $this->assertSame('technical', $model->category);
        $this->assertSame('backend', $model->sub_category);
        $this->assertNull($model->parent_job_id);
        $this->assertSame('Description', $model->description);
        $this->assertSame(4, $model->version);
        $this->assertNotNull($model->deleted_at);
    }

    public function testToDomainRestoresRequirementIdsFromLoadedRelation(): void
    {
        $model = $this->model();
        $model->setRelation('requirements', new Collection([$this->requirementRow($model)]));

        $job = (new JobMapper())->toDomain($model);

        $this->assertSame($model->id, $job->id()->value());
        $this->assertSame('Software Engineer', $job->title());
        $this->assertSame('technical', $job->category());
        $this->assertSame('backend', $job->subCategory());
        $this->assertNull($job->parentJobId());
        $this->assertSame(4, $job->version());
        $this->assertNotNull($job->deletedAt());
        $this->assertCount(1, $job->requirementIds());
        $this->assertSame(
            '44444444-4444-4444-4444-444444444444',
            $job->requirementIds()[0]->value(),
        );
    }

    private function domainJob(): Job
    {
        return Job::reconstitute(
            JobId::fromString('22222222-2222-2222-2222-222222222222'),
            'Software Engineer',
            'technical',
            'backend',
            null,
            'Description',
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-02-01 10:00:00'),
            4,
            new DateTimeImmutable('2025-03-01 10:00:00'),
            [RequirementId::fromString('44444444-4444-4444-4444-444444444444')],
        );
    }

    private function model(): JobModel
    {
        $model = new JobModel();
        $model->id = '22222222-2222-2222-2222-222222222222';
        $model->title = 'Software Engineer';
        $model->category = 'technical';
        $model->sub_category = 'backend';
        $model->parent_job_id = null;
        $model->description = 'Description';
        $model->created_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $model->updated_at = new DateTimeImmutable('2025-02-01 10:00:00');
        $model->version = 4;
        $model->deleted_at = new DateTimeImmutable('2025-03-01 10:00:00');

        return $model;
    }

    private function requirementRow(JobModel $model): JobRequirementModel
    {
        $row = new JobRequirementModel();
        $row->job_id = $model->id;
        $row->requirement_id = '44444444-4444-4444-4444-444444444444';

        return $row;
    }
}
