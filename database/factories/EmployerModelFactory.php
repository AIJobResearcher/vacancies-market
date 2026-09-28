<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\EmployerModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<EmployerModel>
 */
final class EmployerModelFactory extends Factory
{
    /** @var class-string<EmployerModel> */
    protected $model = EmployerModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'title' => fake()->company(),
            'description' => fake()->paragraph(random_int(1, 3)),
            'contacts' => [
                ['type' => 'website', 'value' => fake()->url()],
                ['type' => 'email', 'value' => fake()->companyEmail()],
                ['type' => 'phone', 'value' => fake()->phoneNumber()],
            ],
            'logo_url' => fake()->imageUrl(),
            'location_ids' => [],
            'version' => 1,
        ];
    }
}
