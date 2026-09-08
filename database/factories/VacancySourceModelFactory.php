<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\VacancyModel;
use App\Infrastructure\Eloquents\Models\VacancySourceModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<VacancySourceModel>
 */
final class VacancySourceModelFactory extends Factory
{
    /** @var class-string<VacancySourceModel> */
    protected $model = VacancySourceModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'vacancy_id' => VacancyModel::factory(),
            'source_key' => fake()->randomElement(['linkedin', 'djinni', 'hh', 'indeed', 'stackoverflow']),
            'external_vacancy_id' => fake()->unique()->numerify('###'),
            'external_url' => fake()->url(),
            'first_seen_at' => fake()->dateTime(),
            'last_seen_at' => fake()->dateTime(),
            'closed_at' => null,
            'is_primary' => true,
        ];
    }
}
