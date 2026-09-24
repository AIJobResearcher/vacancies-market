<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Eloquents\Models\EmployerModel;
use App\Infrastructure\Eloquents\Models\InterviewerModel;
use App\Infrastructure\Eloquents\Models\InterviewerVacancyAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class InterviewerSeeder extends Seeder
{
    private const int TOTAL = 4000;

    private const int CHUNK = 1000;

    private const int BULK_CHUNK = 3000;

    private const int MIN_ASSIGNED = 2;

    private const int MUX_ASSIGNED = 4;

    public function run(): void
    {
        DB::transaction(function (): void {
            $employerIds = EmployerModel::query()->pluck('id')->values()->all();

            $interviewerIds = $this->createInterviewers($employerIds);

            VacancyModel::query()
                ->select('id')
                ->chunkById(
                    self::CHUNK,
                    fn ($vacancies) => $this->createVacancyAssignments(
                        $interviewerIds,
                        $vacancies->pluck('id')->all(),
                    ),
                );


        });
    }

    /**
     * @param  list<string>  $employerIds
     * @return list<string>
     */
    private function createInterviewers(array $employerIds): array
    {
        $interviewerIds = [];
        $index = 0;
        $employerCount = count($employerIds);

        for ($offset = 0; $offset < self::TOTAL; $offset += self::CHUNK) {
            $size = min(self::CHUNK, self::TOTAL - $offset);
            $interviewers = InterviewerModel::factory()
                ->count($size)
                ->state(function (array $attributes) use ($employerIds, $employerCount, &$index): array {
                    return ['employer_id' => $employerIds[$index++ % $employerCount]];
                })
                ->create();

            foreach ($interviewers as $interviewer) {
                $interviewerIds[] = $interviewer->id;
            }
        }

        return $interviewerIds;
    }

    /**
     * @param  list<string>  $interviewerIds
     * @param  list<string>  $vacancyIds
     */
    private function createVacancyAssignments(array $interviewerIds, array $vacancyIds): void
    {
        $rows = [];

        foreach ($vacancyIds as $vacancyId) {
            shuffle($interviewerIds);
            $assignedCount = rand(self::MIN_ASSIGNED, self::MUX_ASSIGNED);
            $assignedInterviewerIds = array_slice($interviewerIds, 0, $assignedCount);

            $assignedAt = now()->toDateTimeString();

            foreach ($assignedInterviewerIds as $interviewerId) {
                $rows[] = [
                    'id' => Str::uuid(),
                    'interviewer_id' => $interviewerId,
                    'vacancy_id' => $vacancyId,
                    'assigned_at' => $assignedAt,
                    'unassigned_at' => null,
                    'version' => 1,
                ];
            }
        }

        foreach (array_chunk($rows, self::BULK_CHUNK) as $chunk) {
            InterviewerVacancyAssignmentModel::query()->insert($chunk);
        }
    }
}
