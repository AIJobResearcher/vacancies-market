<?php

namespace Database\Seeders;

use App\Infrastructure\Models\Employer;
use App\Infrastructure\Models\Portal;
use App\Infrastructure\Models\Vacancy;
use App\Infrastructure\Models\Interviewer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoadTestDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the database with large volumes of test data.
     * 
     * WARNING: This seeder creates thousands of records. Use with caution!
     * Usage: php artisan db:seed --class=LoadTestDataSeeder
     */
    public function run(): void
    {
        $this->command->info('Starting LoadTestDataSeeder...');

        $this->command->info('Creating portals...');
        Portal::factory(6)->create();

        $this->command->info('Creating employers and related records...');
        
        // Create employers in batches
        $batchSize = 50;
        for ($i = 0; $i < 5; $i++) {
            $employers = Employer::factory($batchSize)->create();

            foreach ($employers as $employer) {
                // Create vacancies
                Vacancy::factory(fake()->numberBetween(3, 8))
                    ->state(['employer_id' => $employer->id])
                    ->create();

                // Create interviewers
                Interviewer::factory(fake()->numberBetween(1, 3))
                    ->state(['employer_id' => $employer->id])
                    ->create();
            }

            $this->command->info("Created batch " . ($i + 1) . " of employers");
        }

        $this->command->info('LoadTestDataSeeder completed!');
        $this->command->info('Total employers: ' . Employer::count());
        $this->command->info('Total vacancies: ' . Vacancy::count());
        $this->command->info('Total interviewers: ' . Interviewer::count());
    }
}
