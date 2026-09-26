<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Eloquents\Models\RequirementModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class RequirementSeeder extends Seeder
{
    private const int TOTAL = 15000;

    private const int CHUNK = 1000;

    public function run(): void
    {
        DB::transaction(function (): void {
            $uniqueTitles = fake()->unique();

            for ($offset = 0; $offset < self::TOTAL; $offset += self::CHUNK) {
                RequirementModel::factory()
                    ->count(self::CHUNK)
                    ->state(fn (): array => ['title' => $uniqueTitles->words(3, true)])
                    ->create();
            }
        });
    }
}
