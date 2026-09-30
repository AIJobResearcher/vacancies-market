<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\LocationTypeEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Infrastructure\Eloquents\Models\EmployerModel;
use App\Infrastructure\Eloquents\Models\LocationModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<VacancyModel>
 */
final class VacancyModelFactory extends Factory
{
    /** @var list<int>|null */
    private static ?array $countryIds = null;

    /** @var class-string<VacancyModel> */
    protected $model = VacancyModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'employer_id' => EmployerModel::factory(),
            'title' => fake()->jobTitle(),
            'min_salary' => fake()->numberBetween(0, 5000),
            'max_salary' => fake()->optional(0.6)->numberBetween(3001, 10000),
            'status' => 'open',
            'employment_types' => $this->randomEmploymentTypes(),
            'workplaces' => $this->randomWorkplaces(),
            'researcher_location_ids' => $this->researcherLocationIds(),
            'closed_at' => null,
            'version' => 1,
        ];
    }

    /**
     * One to three existing country Locations (5.4.1); empty when the dictionary
     * has not been seeded yet.
     *
     * @return list<int>
     */
    private function researcherLocationIds(): array
    {
        $countryIds = self::countryIds();

        if ($countryIds === []) {
            return [];
        }

        $take = min(random_int(1, 3), count($countryIds));
        $ids = [];

        foreach ((array) array_rand($countryIds, $take) as $key) {
            $ids[] = $countryIds[$key];
        }

        return $ids;
    }

    /** @return list<int> */
    private static function countryIds(): array
    {
        if (self::$countryIds === null) {
            self::$countryIds = LocationModel::query()
                ->where('type', LocationTypeEnum::COUNTRY->value)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->values()
                ->all();
        }

        return self::$countryIds;
    }

    /** @return list<string> */
    private function randomEmploymentTypes(): array
    {
        return $this->randomValues(array_map(
            static fn (EmploymentTypeEnum $type): string => $type->value,
            EmploymentTypeEnum::cases(),
        ));
    }

    /** @return list<string> */
    private function randomWorkplaces(): array
    {
        return $this->randomValues(array_map(
            static fn (WorkplaceEnum $workplace): string => $workplace->value,
            WorkplaceEnum::cases(),
        ));
    }

    /**
     * One to all values, in random order.
     *
     * @param  list<string>  $values
     * @return list<string>
     */
    private function randomValues(array $values): array
    {
        $keys = (array) array_rand($values, random_int(1, count($values)));
        shuffle($keys);

        $picked = [];
        foreach ($keys as $key) {
            $picked[] = $values[$key];
        }

        return $picked;
    }
}
