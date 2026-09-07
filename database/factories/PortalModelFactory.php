<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\PortalModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<PortalModel>
 */
final class PortalModelFactory extends Factory
{
    /** @var class-string<PortalModel> */
    protected $model = PortalModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => fake()->unique()->company(),
            'base_url' => fake()->url(),
            'api_endpoint' => fake()->optional()->url(),
            'crawl_delay_seconds' => fake()->numberBetween(1, 30),
            'version' => 1,
        ];
    }
}
