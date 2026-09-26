<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\VacancyJobAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<VacancyJobAssignmentModel>
 */
final class VacancyJobAssignmentModelFactory extends Factory
{
    /** @var class-string<VacancyJobAssignmentModel> */
    protected $model = VacancyJobAssignmentModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'vacancy_id' => VacancyModel::factory(),
            'job_id' => JobModel::factory(),
            'assigned_at' => fake()->dateTime(),
            'unassigned_at' => null,
            'relevance_score' => fake()->optional()->numberBetween(1, 100),
            'version' => 1,
        ];
    }
}
