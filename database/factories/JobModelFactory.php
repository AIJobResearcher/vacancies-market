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
            'category' => fake()->optional()->word(),
            'sub_category' => fake()->optional()->word(),
            'parent_job_id' => null,
            'description' => fake()->optional()->paragraph(),
            'version' => 1,
            'deleted_at' => null,
        ];
    }
}
