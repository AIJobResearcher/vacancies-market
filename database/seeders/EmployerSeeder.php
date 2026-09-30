<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Enums\LocationTypeEnum;
use App\Infrastructure\Eloquents\Models\EmployerModel;
use App\Infrastructure\Eloquents\Models\LocationModel;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class EmployerSeeder extends Seeder
{
    private const int TOTAL = 3000;

    private const int CHUNK = 1000;

    public function run(): void
    {
        DB::transaction(function (): void {
            $cityIds = $this->cityIds();

            for ($offset = 0; $offset < self::TOTAL; $offset += self::CHUNK) {
                EmployerModel::factory()
                    ->count(self::CHUNK)
                    ->state(new Sequence(
                        fn (): array => ['location_ids' => $this->randomLocationIds($cityIds)],
                    ))
                    ->create();
            }
        });
    }

    /** @return list<int> */
    private function cityIds(): array
    {
        return LocationModel::query()
            ->where('type', LocationTypeEnum::CITY->value)
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * @param list<int> $pool
     * @return list<int>
     */
    private function randomLocationIds(array $pool): array
    {
        if ($pool === []) {
            return [];
        }

        $take = min(random_int(1, 3), count($pool));
        $ids = [];

        foreach ((array) array_rand($pool, $take) as $key) {
            $ids[] = $pool[$key];
        }

        return $ids;
    }
}
