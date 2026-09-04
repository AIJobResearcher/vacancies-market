<?php

namespace Database\Seeders;

use App\Infrastructure\Models\Employer;
use App\Infrastructure\Models\Portal;
use App\Infrastructure\Models\Vacancy;
use App\Infrastructure\Models\Interviewer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create portals
        $portals = Portal::factory(3)->create();

        // Create employers and vacancies
        $employers = Employer::factory(10)->create();

        foreach ($employers as $employer) {
            // Create vacancies for each employer
            Vacancy::factory(fake()->numberBetween(2, 5))
                ->state([
                    'employer_id' => $employer->id,
                ])
                ->create();

            // Create interviewers for each employer
            Interviewer::factory(fake()->numberBetween(1, 3))
                ->state([
                    'employer_id' => $employer->id,
                ])
                ->create();
        }
    }
}
