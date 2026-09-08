<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\RequirementModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use App\Infrastructure\Eloquents\Models\VacancyRequirementAssignmentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<VacancyRequirementAssignmentModel>
 */
final class VacancyRequirementAssignmentModelFactory extends Factory
{
    /** @var class-string<VacancyRequirementAssignmentModel> */
    protected $model = VacancyRequirementAssignmentModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'vacancy_id' => VacancyModel::factory(),
            'requirement_id' => RequirementModel::factory(),
            'assigned_at' => fake()->dateTime(),
            'version' => 1,
        ];
    }
}
