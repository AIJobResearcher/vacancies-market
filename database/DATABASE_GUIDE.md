# Database Migrations, Factories & Seeders Guide

## Overview

This project uses Laravel migrations, factories, and seeders to manage the database.

### Database Structure

- **migrations/** - Database schema definitions
  - `create_users_table.php` - Users authentication
  - `create_employers_table.php` - Job employers
  - `create_vacancies_table.php` - Job vacancies
  - `create_interviewers_table.php` - Company interviewers
  - `create_portals_table.php` - Job portals (LinkedIn, Djinni, etc.)

- **factories/** - Fake data generators
  - `EmployerFactory.php` - Generate test employers
  - `VacancyFactory.php` - Generate test vacancies
  - `InterviewerFactory.php` - Generate test interviewers
  - `PortalFactory.php` - Generate test portals

- **seeders/** - Data seeders
  - `DatabaseSeeder.php` - Basic seed with 10 employers, 3-5 vacancies per employer, 1-3 interviewers per employer
  - `TestDataSeeder.php` - Create specific test data (20 employers with relations)
  - `LoadTestDataSeeder.php` - Create large volumes (250+ employers, 1000+ vacancies)

## Quick Start

### 1. Fresh Database Setup

```bash
# Run all migrations and DatabaseSeeder
php artisan migrate --seed

# Or with fresh start (drops all tables)
php artisan migrate:fresh --seed
```

### 2. Create Test Data Only

```bash
# Run specific seeder
php artisan db:seed --class=TestDataSeeder

# Run load test seeder (creates large volumes)
php artisan db:seed --class=LoadTestDataSeeder
```

### 3. Reset Database

```bash
# Drop all tables and re-run migrations
php artisan migrate:fresh

# Reset migrations with seeding
php artisan migrate:fresh --seed
```

### 4. Rollback Migrations

```bash
# Rollback last migration batch
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset
```

## Creating Records Manually

In tinker shell or code:

```php
// Create single user
$user = User::factory()->create();

// Create multiple employers
$employers = Employer::factory(5)->create();

// Create vacancy with specific employer
$vacancy = Vacancy::factory()
    ->state(['employer_id' => $employer->id])
    ->open()
    ->create();

// Create multiple interviewers
$interviewers = Interviewer::factory(3)
    ->state(['employer_id' => $employer->id])
    ->create();
```

## Artisan Tinker

```bash
# Start tinker shell
php artisan tinker

# Example queries
>>> User::count()
>>> Employer::with('vacancies')->first()
>>> Vacancy::where('status', 'open')->count()
```

## Database Statistics

After running `DatabaseSeeder`:
- **Employers:** 10
- **Vacancies:** 20-50 (2-5 per employer)
- **Interviewers:** 10-30 (1-3 per employer)
- **Portals:** 3

## Notes

- All IDs are UUIDs (not auto-incrementing)
- Timestamps are automatically set by factories
- `LoadTestDataSeeder` creates 250 employers + 1000+ vacancies (useful for performance testing)
- Foreign key constraints are enforced (cascade delete)
- JSON fields (requirements, vacancy_ids, parsing_config) use Faker for realistic data
