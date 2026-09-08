<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\EmployerModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<VacancyModel>
 */
final class VacancyModelFactory extends Factory
{
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
            'description' => fake()->optional()->paragraph(),
            'salary_min' => fake()->numberBetween(1000, 3000),
            'salary_max' => fake()->optional()->numberBetween(3001, 10000),
            'salary_currency' => 'USD',
            'status' => 'open',
            'country' => fake()->optional()->country(),
            'city' => fake()->optional()->city(),
            'employment_type' => fake()->randomElement(['part-time', 'contract', 'internship', 'full-time', 'volunteer']),
            'workplace' => fake()->randomElement(['remote', 'on-site', 'hybrid']),
            'posted_at' => fake()->dateTime(),
            'closed_at' => null,
            'version' => 1,
            'external_urls' => [fake()->url()],
            'internal_url' => fake()->optional()->url(),
        ];
    }
}
