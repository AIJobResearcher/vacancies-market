<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Eloquents\Models\PortalModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PortalSeeder extends Seeder
{
    private const int TOTAL = 10;

    public function run(): void
    {
        DB::transaction(function (): void {
            PortalModel::factory()->count(self::TOTAL)->create();
        });
    }
}
