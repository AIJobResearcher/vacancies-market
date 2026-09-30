<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class JobSeeder extends Seeder
{
    private const int TOTAL = 2000;

    private const int CHUNK = 1000;

    private const int BULK_CHUNK = 5000;

    private const int CHILD_JOB_PERCENT = 10;

    public function run(): void
    {
        DB::transaction(function (): void {
            $jobIds = $this->createJobs();

            $this->assignParentJobs($jobIds);
            $this->assignRequirements($jobIds);
        });
    }

    /** @return list<string> */
    private function createJobs(): array
    {
        $jobIds = [];

        for ($offset = 0; $offset < self::TOTAL; $offset += self::CHUNK) {
            $size = min(self::CHUNK, self::TOTAL - $offset);
            $jobs = JobModel::factory()->count($size)->create();

            foreach ($jobs as $job) {
                $jobIds[] = $job->id;
            }
        }

        return $jobIds;
    }

    /**
     * The first half stays root jobs (5.2.1 `parent_job_id` is nullable), part of
     * the second half is attached to an existing root job — never to a fresh uuid.
     *
     * @param list<string> $jobIds
     */
    private function assignParentJobs(array $jobIds): void
    {
        $rootIds = array_slice($jobIds, 0, intdiv(count($jobIds), 2));

        if ($rootIds === []) {
            return;
        }

        foreach (array_slice($jobIds, count($rootIds)) as $jobId) {
            if (random_int(1, 100) > self::CHILD_JOB_PERCENT) {
                continue;
            }

            JobModel::query()
                ->whereKey($jobId)
                ->update(['parent_job_id' => $rootIds[array_rand($rootIds)]]);
        }
    }

    /** @param list<string> $jobIds */
    private function assignRequirements(array $jobIds): void
    {
        $requirementIds = RequirementModel::query()->pluck('id')->values()->all();
        shuffle($requirementIds);

        $rows = [];
        $requirementCount = count($requirementIds);
        $pointer = 0;

        foreach ($jobIds as $jobId) {
            $take = random_int(1, 3);
            for ($i = 0; $i < $take; $i++) {
                $rows[] = [
                    'job_id' => $jobId,
                    'requirement_id' => $requirementIds[$pointer % $requirementCount],
                ];
                $pointer++;
            }
        }

        foreach (array_chunk($rows, self::BULK_CHUNK) as $chunk) {
            DB::table('job_requirements')->insert($chunk);
        }
    }
}
