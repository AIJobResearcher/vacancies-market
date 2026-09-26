<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\JobModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<JobModel>
 */
final class JobModelFactory extends Factory
{
    /** @var class-string<JobModel> */
    protected $model = JobModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'title' => fake()->jobTitle(),
            'category' => fake()->word() . " Job Category",
            'sub_category' => fake()->word() . " Job Sub-Category",
            'parent_job_id' => fake()->optional(0.1)->uuid(),
            'description' => fake()->optional()->paragraph(),
            'version' => 1,
            'deleted_at' => null,
        ];
    }
}
