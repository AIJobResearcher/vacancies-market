<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Infrastructure\Eloquents\Models\VacancyJobAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use App\Infrastructure\Eloquents\Models\VacancyRequirementAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancySourceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VacancyModelTest extends TestCase
{
    use RefreshDatabase;

    public function testVacancyPersistsAndRestores(): void
    {
        DB::table('employers')->insert([
            'id' => '11111111-1111-1111-1111-111111111111',
            'title' => 'Acme',
            'version' => 1,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);

        $model = new VacancyModel();
        $model->id = '22222222-2222-2222-2222-222222222222';
        $model->employer_id = '11111111-1111-1111-1111-111111111111';
        $model->title = 'Senior Engineer';
        $model->description = null;
        $model->salary_min = 1000;
        $model->salary_max = 2000;
        $model->salary_currency = 'USD';
        $model->status = 'open';
        $model->country = 'USA';
        $model->city = 'NYC';
        $model->employment_type = 'full-time';
        $model->workplace = 'remote';
        $model->posted_at = '2025-01-01 10:00:00';
        $model->version = 1;
        $model->external_urls = ['https://example.com/vacancy'];
        $model->save();

        $loaded = VacancyModel::query()->findOrFail('22222222-2222-2222-2222-222222222222');

        $this->assertSame('Senior Engineer', $loaded->title);
        $this->assertSame(['https://example.com/vacancy'], $loaded->external_urls);
        $this->assertSame('full-time', $loaded->employment_type);
    }

    public function testVacancyRelationsEagerLoadChildren(): void
    {
        $vacancyId = '22222222-2222-2222-2222-222222222222';
        $employerId = '11111111-1111-1111-1111-111111111111';
        DB::table('employers')->insert([
            'id' => $employerId,
            'title' => 'Acme',
            'version' => 1,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);
        DB::table('vacancies')->insert([
            'id' => $vacancyId,
            'employer_id' => $employerId,
            'title' => 'Role',
            'description' => null,
            'salary_min' => 1000,
            'salary_max' => null,
            'salary_currency' => 'USD',
            'status' => 'open',
            'country' => null,
            'city' => null,
            'employment_type' => 'contract',
            'workplace' => 'hybrid',
            'posted_at' => '2025-01-01 10:00:00',
            'version' => 1,
            'external_urls' => json_encode(['https://example.com']),
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);

        $req = new VacancyRequirementAssignmentModel();
        $req->id = '33333333-3333-3333-3333-333333333333';
        $req->vacancy_id = $vacancyId;
        $req->requirement_id = '44444444-4444-4444-4444-444444444444';
        $req->assigned_at = '2025-01-02 10:00:00';
        $req->version = 1;
        $req->save();

        $job = new VacancyJobAssignmentModel();
        $job->id = '55555555-5555-5555-5555-555555555555';
        $job->vacancy_id = $vacancyId;
        $job->job_id = '66666666-6666-6666-6666-666666666666';
        $job->assigned_at = '2025-01-03 10:00:00';
        $job->unassigned_at = null;
        $job->relevance_score = 80;
        $job->version = 1;
        $job->save();

        $source = new VacancySourceModel();
        $source->id = '77777777-7777-7777-7777-777777777777';
        $source->vacancy_id = $vacancyId;
        $source->source_key = 'linkedin';
        $source->external_vacancy_id = 'ext123';
        $source->external_url = 'https://linkedin.com/123';
        $source->first_seen_at = '2025-01-01 10:00:00';
        $source->last_seen_at = '2025-01-01 10:00:00';
        $source->is_primary = true;
        $source->save();

        $loaded = VacancyModel::query()
            ->with(['requirementAssignments', 'jobAssignments', 'sources'])
            ->findOrFail($vacancyId);

        $this->assertCount(1, $loaded->requirementAssignments);
        $this->assertCount(1, $loaded->jobAssignments);
        $this->assertCount(1, $loaded->sources);
        $this->assertTrue($loaded->sources->first()->is_primary);
        $this->assertEquals(
            '2025-01-03 10:00:00',
            $loaded->jobAssignments->first()->assigned_at->format('Y-m-d H:i:s')
        );
    }
}
