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
            'min_salary' => fake()->numberBetween(0, 5000),
            'max_salary' => fake()->optional(0.6)->numberBetween(3001, 10000),
            'status' => 'open',
            'employment_types' => [
                fake()->randomElement(['part-time', 'contract', 'internship', 'full-time', 'volunteer']),
            ],
            'workplaces' => [
                fake()->randomElement(['remote', 'on-site', 'hybrid']),
            ],
            'researcher_location_ids' => [],
            'closed_at' => null,
            'version' => 1,
        ];
    }
}
