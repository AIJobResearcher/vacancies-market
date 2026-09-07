<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Eloquents\Models\EmployerModel;
use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use App\Infrastructure\Eloquents\Models\VacancyJobAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use App\Infrastructure\Eloquents\Models\VacancyRequirementAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancySourceModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class VacancySeeder extends Seeder
{
    private const int TOTAL = 15000;

    private const int CHUNK = 1000;

    private const int BULK_CHUNK = 5000;

    /** @var list<string> */
    private const array SOURCE_KEYS = ['linkedin', 'djinni', 'hh', 'indeed', 'stackoverflow'];

    public function run(): void
    {
        DB::transaction(function (): void {
            $employerIds = EmployerModel::query()->pluck('id')->values()->all();
            $jobIds = JobModel::query()->pluck('id')->values()->all();
            $requirementIds = RequirementModel::query()->pluck('id')->values()->all();

            $vacancyIds = $this->createVacancies($employerIds);
            $assignedAt = now()->toDateTimeString();

            $this->createJobAssignments($vacancyIds, $jobIds, $assignedAt);
            $this->createRequirementAssignments($vacancyIds, $requirementIds, $assignedAt);
            $this->createSources($vacancyIds, $assignedAt);
        });
    }

    /**
     * @param  list<string>  $employerIds
     * @return list<string>
     */
    private function createVacancies(array $employerIds): array
    {
        $vacancyIds = [];
        $index = 0;
        $employerCount = count($employerIds);
        $now = now();

        for ($offset = 0; $offset < self::TOTAL; $offset += self::CHUNK) {
            $size = min(self::CHUNK, self::TOTAL - $offset);
            $vacancies = VacancyModel::factory()
                ->count($size)
                ->state(function (array $attributes) use ($employerIds, $employerCount, &$index): array {
                    return ['employer_id' => $employerIds[$index++ % $employerCount]];
                })
                ->make();

            $batch = [];
            foreach ($vacancies as $vacancy) {
                $vacancy->created_at = $now;
                $vacancy->updated_at = $now;
                $batch[] = $vacancy->getAttributes();
                $vacancyIds[] = $vacancy->id;
            }

            VacancyModel::query()->insert($batch);
        }

        return $vacancyIds;
    }

    /**
     * @param  list<string>  $vacancyIds
     * @param  list<string>  $jobIds
     */
    private function createJobAssignments(array $vacancyIds, array $jobIds, string $assignedAt): void
    {
        $jobCount = count($jobIds);
        $rows = [];

        foreach ($vacancyIds as $index => $vacancyId) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'vacancy_id' => $vacancyId,
                'job_id' => $jobIds[$index % $jobCount],
                'assigned_at' => $assignedAt,
                'unassigned_at' => null,
                'relevance_score' => random_int(1, 100),
                'version' => 1,
            ];
        }

        foreach (array_chunk($rows, self::BULK_CHUNK) as $chunk) {
            VacancyJobAssignmentModel::query()->insert($chunk);
        }
    }

    /**
     * @param  list<string>  $vacancyIds
     * @param  list<string>  $requirementIds
     */
    private function createRequirementAssignments(array $vacancyIds, array $requirementIds, string $assignedAt): void
    {
        $requirementCount = count($requirementIds);
        shuffle($requirementIds);
        $pointer = 0;
        $rows = [];

        foreach ($vacancyIds as $vacancyId) {
            $take = random_int(1, 2);
            for ($i = 0; $i < $take; $i++) {
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'vacancy_id' => $vacancyId,
                    'requirement_id' => $requirementIds[$pointer % $requirementCount],
                    'assigned_at' => $assignedAt,
                    'version' => 1,
                ];
                $pointer++;
            }
        }

        foreach (array_chunk($rows, self::BULK_CHUNK) as $chunk) {
            VacancyRequirementAssignmentModel::query()->insert($chunk);
        }
    }

    /** @param  list<string>  $vacancyIds */
    private function createSources(array $vacancyIds, string $assignedAt): void
    {
        $sourceKeyCount = count(self::SOURCE_KEYS);
        $rows = [];

        foreach ($vacancyIds as $index => $vacancyId) {
            $sourceKey = self::SOURCE_KEYS[$index % $sourceKeyCount];
            $rows[] = [
                'id' => (string) Str::uuid(),
                'vacancy_id' => $vacancyId,
                'source_key' => $sourceKey,
                'external_vacancy_id' => (string) Str::uuid(),
                'external_url' => 'https://' . $sourceKey . '.example.com/vacancies/' . $vacancyId,
                'first_seen_at' => $assignedAt,
                'last_seen_at' => $assignedAt,
                'closed_at' => null,
                'is_primary' => true,
            ];
        }

        foreach (array_chunk($rows, self::BULK_CHUNK) as $chunk) {
            VacancySourceModel::query()->insert($chunk);
        }
    }
}
