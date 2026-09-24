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
    private const int MIN_VACANCIES_PER_JOB = 3;

    private const int MAX_VACANCIES_PER_JOB = 100;

    private const int CHUNK = 1000;

    private const int BULK_CHUNK = 2000;

    /** @var list<string> */
    private const array SOURCE_KEYS = ['linkedin', 'djinni', 'hh', 'indeed', 'stackoverflow'];

    public function run(): void
    {
        DB::transaction(function (): void {
            $employerIds    = EmployerModel::query()->pluck('id')->values()->all();
            $jobIds         = JobModel::query()->pluck('id')->values()->all();
            $requirementIds = RequirementModel::query()->pluck('id')->values()->all();

            $vacancyCounts = $this->planVacanciesPerJob($jobIds);
            $assignedAt    = now()->toDateTimeString();

            foreach ($this->createVacancies($employerIds, array_sum($vacancyCounts)) as $chunk) {
                $chunkCounts = $this->takeVacancyCounts($vacancyCounts, count($chunk));

                $this->createJobAssignments($chunk, $chunkCounts, $assignedAt);
                $this->createRequirementAssignments($chunk, $requirementIds, $assignedAt);
                $this->createSources($chunk, $assignedAt);
            }
        });
    }

    /**
     *
     * @param list<string> $jobIds
     * @return array<string, int>
     */
    private function planVacanciesPerJob(array $jobIds): array
    {
        $vacancyCounts = [];

        foreach ($jobIds as $jobId) {
            $vacancyCounts[$jobId] = random_int(self::MIN_VACANCIES_PER_JOB, self::MAX_VACANCIES_PER_JOB);
        }

        return $vacancyCounts;
    }

    private function takeVacancyCounts(array &$vacancyCounts, int $limit): array
    {
        $taken = [];

        foreach ($vacancyCounts as $jobId => $count) {
            if ($limit <= 0) {
                break;
            }

            if ($count <= $limit) {
                $taken[$jobId] = $count;
                $limit -= $count;
                unset($vacancyCounts[$jobId]);
            } else {
                $taken[$jobId] = $limit;
                $vacancyCounts[$jobId] = $count - $limit;
                $limit = 0;
            }
        }

        return $taken;
    }

    /**
     * @param  list<string>  $employerIds
     * @return list<string>
     */
    private function createVacancies(array $employerIds, int $total): \Generator
    {
        $index = 0;
        $employerCount = count($employerIds);
        $now = now();

        for ($offset = 0; $offset < $total; $offset += self::CHUNK) {
            $size = min(self::CHUNK, $total - $offset);

            $vacancies = VacancyModel::factory()
                ->count($size)
                ->state(function (array $attributes) use ($employerIds, $employerCount, &$index): array {
                    return ['employer_id' => $employerIds[$index++ % $employerCount]];
                })
                ->make();

            $batch = [];
            $ids = [];
            foreach ($vacancies as $vacancy) {
                $vacancy->created_at = $now;
                $vacancy->updated_at = $now;
                $batch[] = $vacancy->getAttributes();
                $ids[] = $vacancy->id;
            }

            VacancyModel::query()->insert($batch);

            yield $ids;
        }
    }

    /**
     * @param  list<string>  $vacancyIds
     * @param  array<string, int>  $vacancyCounts
     */
    private function createJobAssignments(array $vacancyIds, array $vacancyCounts, string $assignedAt): void
    {
        $rows = [];
        $pointer = 0;

        foreach ($vacancyCounts as $jobId => $count) {
            for ($i = 0; $i < $count; $i++) {
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'vacancy_id' => $vacancyIds[$pointer],
                    'job_id' => $jobId,
                    'assigned_at' => $assignedAt,
                    'unassigned_at' => null,
                    'relevance_score' => random_int(1, 100),
                    'version' => 1,
                ];
                $pointer++;

                if (count($rows) === self::BULK_CHUNK) {
                    VacancyJobAssignmentModel::query()->insert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            VacancyJobAssignmentModel::query()->insert($rows);
        }
    }

    /**
     * @param  list<string>  $vacancyIds
     * @param  list<string>  $requirementIds
     */
    private function createRequirementAssignments(array $vacancyIds, array $requirementIds, string $assignedAt): void
    {
        $pointer = 0;
        $rows = [];

        foreach ($vacancyIds as $vacancyId) {
            $take = random_int(10, 25);

            for ($i = 0; $i < $take; $i++) {
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'vacancy_id' => $vacancyId,
                    'requirement_id' => $requirementIds === [] ? null : $requirementIds[array_rand($requirementIds)],
                    'assigned_at' => $assignedAt,
                    'version' => 1,
                ];
                $pointer++;

                if (count($rows) === self::BULK_CHUNK) {
                    VacancyRequirementAssignmentModel::query()->insert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            VacancyRequirementAssignmentModel::query()->insert($rows);
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

            if (count($rows) === self::BULK_CHUNK) {
                VacancySourceModel::query()->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            VacancySourceModel::query()->insert($rows);
        }
    }
}
