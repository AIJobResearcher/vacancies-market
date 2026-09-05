<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Infrastructure\Eloquents\Models\InterviewerModel;
use App\Infrastructure\Eloquents\Models\InterviewerVacancyAssignmentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InterviewerModelTest extends TestCase
{
    use RefreshDatabase;

    private const EMPLOYER_ID = '11111111-1111-1111-1111-111111111111';

    public function testInterviewerPersistsAndRestores(): void
    {
        $this->insertEmployer();

        $model = new InterviewerModel();
        $model->id = '99999999-9999-9999-9999-999999999999';
        $model->employer_id = self::EMPLOYER_ID;
        $model->full_name = 'Alice Smith';
        $model->position = 'Senior';
        $model->profile_urls = ['Linkedin' => 'https://linkedin.com/in/alice'];
        $model->is_active = true;
        $model->version = 1;
        $model->save();

        $loaded = InterviewerModel::query()->findOrFail('99999999-9999-9999-9999-999999999999');

        $this->assertSame('Alice Smith', $loaded->full_name);
        $this->assertSame(['Linkedin' => 'https://linkedin.com/in/alice'], $loaded->profile_urls);
        $this->assertTrue($loaded->is_active);
        $this->assertSame(1, $loaded->version);
    }

    public function testInterviewerRelationsEagerLoadVacancyAssignments(): void
    {
        $interviewerId = '99999999-9999-9999-9999-999999999999';
        $vacancyId = '66666666-6666-6666-6666-666666666666';
        $this->insertEmployer();
        $this->insertVacancy($vacancyId);

        DB::table('interviewers')->insert([
            'id' => $interviewerId,
            'employer_id' => self::EMPLOYER_ID,
            'full_name' => 'Alice Smith',
            'is_active' => true,
            'version' => 1,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);

        $assignment = new InterviewerVacancyAssignmentModel();
        $assignment->id = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $assignment->interviewer_id = $interviewerId;
        $assignment->vacancy_id = $vacancyId;
        $assignment->assigned_at = '2025-01-05 10:00:00';
        $assignment->unassigned_at = null;
        $assignment->version = 1;
        $assignment->save();

        $loaded = InterviewerModel::query()->with('vacancyAssignments')->findOrFail($interviewerId);

        $this->assertCount(1, $loaded->vacancyAssignments);
        $this->assertNull($loaded->vacancyAssignments->first()->unassigned_at);
    }

    private function insertEmployer(): void
    {
        DB::table('employers')->insert([
            'id' => self::EMPLOYER_ID,
            'title' => 'Acme',
            'version' => 1,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);
    }

    private function insertVacancy(string $id): void
    {
        DB::table('vacancies')->insert([
            'id' => $id,
            'employer_id' => self::EMPLOYER_ID,
            'title' => 'Role',
            'description' => null,
            'salary_min' => 0,
            'salary_max' => null,
            'salary_currency' => 'USD',
            'status' => 'open',
            'country' => null,
            'city' => null,
            'employment_type' => 'full-time',
            'workplace' => 'remote',
            'posted_at' => '2025-01-01 10:00:00',
            'version' => 1,
            'external_urls' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ]);
    }
}
