<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\RequirementModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<RequirementModel>
 */
final class RequirementModelFactory extends Factory
{
    /** @var class-string<RequirementModel> */
    protected $model = RequirementModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'title' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'category' => fake()->optional()->word(),
        ];
    }
}
