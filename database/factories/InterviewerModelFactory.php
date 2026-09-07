<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\EmployerModel;
use App\Infrastructure\Eloquents\Models\InterviewerModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<InterviewerModel>
 */
final class InterviewerModelFactory extends Factory
{
    /** @var class-string<InterviewerModel> */
    protected $model = InterviewerModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'employer_id' => EmployerModel::factory(),
            'full_name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'profile_urls' => [
                'Linkedin' => fake()->url(),
            ],
            'is_active' => true,
            'version' => 1,
            'deleted_at' => null,
        ];
    }
}
