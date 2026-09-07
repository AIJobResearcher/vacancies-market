<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\JobRequirementModel;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class JobSeeder extends Seeder
{
    private const int TOTAL = 5000;

    private const int CHUNK = 1000;

    private const int BULK_CHUNK = 5000;

    public function run(): void
    {
        DB::transaction(function (): void {
            $jobIds = $this->createJobs();

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
                JobRequirementModel::query()->insert($chunk);
            }
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
}
