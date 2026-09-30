<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Eloquents\Models\EmployerModel;
use App\Infrastructure\Eloquents\Models\InterviewerModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class InterviewerSeeder extends Seeder
{
    private const int TOTAL = 4000;

    private const int CHUNK = 1000;

    public function run(): void
    {
        DB::transaction(function (): void {
            $employerIds = EmployerModel::query()->pluck('id')->values()->all();

            $this->createInterviewers($employerIds);
        });
    }

    /** @param list<string> $employerIds */
    private function createInterviewers(array $employerIds): void
    {
        $index = 0;
        $employerCount = count($employerIds);

        for ($offset = 0; $offset < self::TOTAL; $offset += self::CHUNK) {
            $size = min(self::CHUNK, self::TOTAL - $offset);
            InterviewerModel::factory()
                ->count($size)
                ->state(function (array $attributes) use ($employerIds, $employerCount, &$index): array {
                    return ['employer_id' => $employerIds[$index++ % $employerCount]];
                })
                ->create();
        }
    }
}
