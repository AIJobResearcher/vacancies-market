<?php

namespace Database\Seeders;

use App\Infrastructure\Models\Employer;
use App\Infrastructure\Models\Portal;
use App\Infrastructure\Models\Vacancy;
use App\Infrastructure\Models\Interviewer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the database with test data for development and testing.
     * 
     * Usage: php artisan db:seed --class=TestDataSeeder
     */
    public function run(): void
    {
        $this->createPortals();
        $this->createEmployersWithRelations();
    }

    /**
     * Create test portals.
     */
    private function createPortals(): void
    {
        Portal::factory()
            ->state([
                'name' => 'LinkedIn',
                'base_url' => 'https://www.linkedin.com',
            ])
            ->create();

        Portal::factory()
            ->state([
                'name' => 'Djinni',
                'base_url' => 'https://djinni.co',
            ])
            ->create();

        Portal::factory()
            ->state([
                'name' => 'HeadHunter',
                'base_url' => 'https://hh.ru',
            ])
            ->create();

        Portal::factory(3)->create();
    }

    /**
     * Create employers with related vacancies and interviewers.
     */
    private function createEmployersWithRelations(): void
    {
        // Create 20 employers with vacancies and interviewers
        Employer::factory(20)->create()->each(function ($employer) {
            // Create 2-5 vacancies per employer
            Vacancy::factory(fake()->numberBetween(2, 5))
                ->state(['employer_id' => $employer->id])
                ->open()
                ->create();

            // Create 1-2 closed vacancies per employer
            Vacancy::factory(fake()->numberBetween(1, 2))
                ->state(['employer_id' => $employer->id])
                ->closed()
                ->create();

            // Create 1-3 interviewers per employer
            Interviewer::factory(fake()->numberBetween(1, 3))
                ->state(['employer_id' => $employer->id])
                ->create();
        });
    }
}
