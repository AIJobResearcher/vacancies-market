<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Eloquents\Models\EmployerModel;
use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\PortalModel;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use App\Infrastructure\Eloquents\Models\SourceModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
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
    private const array SOURCE_CODES = ['linkedin', 'djinni', 'hh', 'indeed', 'stackoverflow'];

    public function run(): void
    {
        DB::transaction(function (): void {
            $employerIds    = EmployerModel::query()->pluck('id')->values()->all();
            $jobIds         = JobModel::query()->pluck('id')->values()->all();
            $requirementIds = RequirementModel::query()->pluck('id')->values()->all();
            /** @var array<string, string> $portalIds */
            $portalIds      = PortalModel::query()->pluck('id', 'code')->all();

            $vacancyCounts = $this->planVacanciesPerJob($jobIds);
            $assignedAt    = now()->toDateTimeString();

            foreach ($this->createVacancies($employerIds, array_sum($vacancyCounts)) as $chunk) {
                $chunkCounts = $this->takeVacancyCounts($vacancyCounts, count($chunk));

                $this->createJobAssignments($chunk, $chunkCounts);
                $this->createRequirementAssignments($chunk, $requirementIds);
                $this->createSources($chunk, $portalIds, $assignedAt);
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
    private function createJobAssignments(array $vacancyIds, array $vacancyCounts): void
    {
        $rows = [];
        $pointer = 0;

        foreach ($vacancyCounts as $jobId => $count) {
            for ($i = 0; $i < $count; $i++) {
                $rows[] = [
                    'vacancy_id' => $vacancyIds[$pointer],
                    'job_id' => $jobId,
                ];
                $pointer++;

                if (count($rows) === self::BULK_CHUNK) {
                    DB::table('vacancy_job_assignments')->insert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            DB::table('vacancy_job_assignments')->insert($rows);
        }
    }

    /**
     * @param  list<string>  $vacancyIds
     * @param  list<string>  $requirementIds
     */
    private function createRequirementAssignments(array $vacancyIds, array $requirementIds): void
    {
        $pointer = 0;
        $rows = [];

        if ($requirementIds === []) {
            return;
        }

        foreach ($vacancyIds as $vacancyId) {
            $take = min(random_int(10, 25), count($requirementIds));
            $pickedIds = array_map(
                static fn (int $key): string => $requirementIds[$key],
                (array) array_rand($requirementIds, $take)
            );

            foreach ($pickedIds as $requirementId) {
                $rows[] = [
                    'vacancy_id' => $vacancyId,
                    'requirement_id' => $requirementId,
                ];
                $pointer++;

                if (count($rows) === self::BULK_CHUNK) {
                    DB::table('vacancy_requirement_assignments')->insert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            DB::table('vacancy_requirement_assignments')->insert($rows);
        }
    }

    /**
     * @param  list<string>  $vacancyIds
     * @param  array<string, string>  $portalIds
     */
    private function createSources(array $vacancyIds, array $portalIds, string $assignedAt): void
    {
        if ($portalIds === []) {
            return;
        }

        $portalCodes = array_keys($portalIds);
        $portalCount = count($portalCodes);
        $rows = [];

        foreach ($vacancyIds as $index => $vacancyId) {
            $portalCode = $portalCodes[$index % $portalCount];
            $rows[] = [
                'id' => (string) Str::uuid(),
                'vacancy_id' => $vacancyId,
                'portal_id' => $portalIds[$portalCode],
                'external_vacancy_id' => (string) Str::uuid(),
                'external_url' => 'https://' . $portalCode . '.example.com/vacancies/' . $vacancyId,
                'title' => fake()->jobTitle(),
                'posted_at' => $assignedAt,
                'created_at' => $assignedAt,
                'updated_at' => $assignedAt,
            ];

            if (count($rows) === self::BULK_CHUNK) {
                SourceModel::query()->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            SourceModel::query()->insert($rows);
        }
    }
}
