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
            'code' => fake()->unique()->slug(2),
            'name' => fake()->unique()->company(),
            'base_url' => fake()->url(),
        ];
    }
}
