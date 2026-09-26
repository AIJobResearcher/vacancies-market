<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class JobModelTest extends TestCase
{
    use RefreshDatabase;

    public function testJobPersistsAndRestores(): void
    {
        $model = new JobModel();
        $model->id = '22222222-2222-2222-2222-222222222222';
        $model->title = 'Software Engineer';
        $model->category = 'technical';
        $model->sub_category = 'backend';
        $model->parent_job_id = null;
        $model->description = 'Description';
        $model->version = 1;
        $model->save();

        $loaded = JobModel::query()->findOrFail('22222222-2222-2222-2222-222222222222');

        $this->assertSame('Software Engineer', $loaded->title);
        $this->assertSame('backend', $loaded->sub_category);
        $this->assertNull($loaded->parent_job_id);
        $this->assertSame(1, $loaded->version);
    }

    public function testJobRelationsEagerLoadRequirements(): void
    {
        $jobId = '22222222-2222-2222-2222-222222222222';
        $requirementId = '88888888-8888-8888-8888-888888888888';

        $job = new JobModel();
        $job->id = $jobId;
        $job->title = 'Software Engineer';
        $job->version = 1;
        $job->save();

        $requirement = new RequirementModel();
        $requirement->id = $requirementId;
        $requirement->title = 'PHP';
        $requirement->save();

        DB::table('job_requirements')->insert([
            'job_id' => $jobId,
            'requirement_id' => $requirementId,
        ]);

        $loaded = JobModel::query()->with('requirements')->findOrFail($jobId);

        $this->assertCount(1, $loaded->requirements);
        $this->assertSame($requirementId, $loaded->requirements->first()->requirement_id);
    }
}
