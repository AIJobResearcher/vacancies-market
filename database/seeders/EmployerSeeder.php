<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Eloquents\Models\EmployerModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class EmployerSeeder extends Seeder
{
    private const int TOTAL = 1000;

    private const int CHUNK = 1000;

    public function run(): void
    {
        DB::transaction(function (): void {
            for ($offset = 0; $offset < self::TOTAL; $offset += self::CHUNK) {
                EmployerModel::factory()->count(self::CHUNK)->create();
            }
        });
    }
}
