<?php

namespace Database\Factories;

use App\Infrastructure\Models\Vacancy;
use App\Infrastructure\Models\Employer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vacancy>
 */
class VacancyFactory extends Factory
{
    protected $model = Vacancy::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Senior PHP Developer',
            'Middle Python Developer',
            'Junior TypeScript/React Developer',
            'DevOps Engineer',
            'QA Automation Engineer',
            'Data Scientist',
            'Full Stack Developer',
            'Frontend Engineer',
            'Backend Engineer',
            'Systems Administrator',
        ];

        $requirements = [
            ['PHP 8+', 'Laravel', 'PostgreSQL', 'Redis', 'Docker'],
            ['Python 3.9+', 'FastAPI', 'Celery', 'PostgreSQL'],
            ['TypeScript', 'React 18', 'Next.js', 'TailwindCSS'],
            ['Docker', 'Kubernetes', 'CI/CD', 'Linux'],
            ['Selenium', 'Pytest', 'REST API testing', 'SQL'],
            ['Python', 'ML algorithms', 'TensorFlow', 'SQL'],
            ['JavaScript', 'Node.js', 'React', 'PostgreSQL', 'Docker'],
            ['Vue.js/React', 'TypeScript', 'CSS', 'Responsive Design'],
            ['Go/Rust', 'Microservices', 'REST/gRPC', 'PostgreSQL'],
            ['Linux', 'Bash', 'Docker', 'Kubernetes', 'Terraform'],
        ];

        $countries = ['Ukraine', 'Poland', 'Germany', 'USA', 'Canada', 'UK', 'Netherlands'];
        $cities = ['Kyiv', 'Lviv', 'Kharkiv', 'Warsaw', 'Berlin', 'New York', 'Toronto', 'London', 'Amsterdam'];

        return [
            'id' => Str::uuid(),
            'employer_id' => Employer::factory(),
            'title' => fake()->randomElement($titles),
            'description' => fake()->paragraphs(3, true),
            'requirements' => fake()->randomElement($requirements),
            'salary_min' => fake()->numberBetween(1000, 3000),
            'salary_max' => fake()->numberBetween(3500, 10000),
            'salary_currency' => 'USD',
            'status' => fake()->randomElement(['open', 'closed']),
            'country' => fake()->randomElement($countries),
            'city' => fake()->randomElement($cities),
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the vacancy is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }

    /**
     * Indicate that the vacancy is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }
}
