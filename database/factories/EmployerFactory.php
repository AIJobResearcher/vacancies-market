<?php

namespace Database\Factories;

use App\Infrastructure\Models\Employer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Employer>
 */
class EmployerFactory extends Factory
{
    protected $model = Employer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyName = fake()->company();

        return [
            'id' => Str::uuid(),
            'name' => $companyName,
            'description' => fake()->text(),
            'website' => fake()->url(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'portal_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
