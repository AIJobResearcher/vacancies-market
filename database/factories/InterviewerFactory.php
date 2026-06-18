<?php

namespace Database\Factories;

use App\Infrastructure\Models\Interviewer;
use App\Infrastructure\Models\Employer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Interviewer>
 */
class InterviewerFactory extends Factory
{
    protected $model = Interviewer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $positions = [
            'HR Manager',
            'Technical Lead',
            'CTO',
            'HR Specialist',
            'Team Lead',
            'Engineering Manager',
            'Product Manager',
            'Recruiter',
            'Senior Recruiter',
            'Head of Engineering',
        ];

        return [
            'id' => Str::uuid(),
            'employer_id' => Employer::factory(),
            'full_name' => fake()->name(),
            'position' => fake()->randomElement($positions),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'portal_id' => null,
            'profile_url' => fake()->url(),
            'vacancy_ids' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
