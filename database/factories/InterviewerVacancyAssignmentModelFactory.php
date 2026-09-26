<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\InterviewerModel;
use App\Infrastructure\Eloquents\Models\InterviewerVacancyAssignmentModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<InterviewerVacancyAssignmentModel>
 */
final class InterviewerVacancyAssignmentModelFactory extends Factory
{
    /** @var class-string<InterviewerVacancyAssignmentModel> */
    protected $model = InterviewerVacancyAssignmentModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'interviewer_id' => InterviewerModel::factory(),
            'vacancy_id' => VacancyModel::factory(),
            'assigned_at' => fake()->dateTime(),
            'unassigned_at' => null,
            'version' => 1,
        ];
    }
}
