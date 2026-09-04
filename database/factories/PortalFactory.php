<?php

namespace Database\Factories;

use App\Infrastructure\Models\Portal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Portal>
 */
class PortalFactory extends Factory
{
    protected $model = Portal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $portals = [
            'LinkedIn' => ['https://www.linkedin.com', '/jobs'],
            'Djinni' => ['https://djinni.co', '/jobs'],
            'HeadHunter' => ['https://hh.ru', '/api/vacancies'],
            'Indeed' => ['https://www.indeed.com', '/jobs'],
            'Stack Overflow Jobs' => ['https://stackoverflow.com', '/jobs'],
            'GitHub Jobs' => ['https://jobs.github.com', '/positions.json'],
        ];

        $portalNames = array_keys($portals);
        $portalName = fake()->randomElement($portalNames);
        [$baseUrl, $endpoint] = $portals[$portalName];

        return [
            'id' => Str::uuid(),
            'name' => $portalName,
            'base_url' => $baseUrl,
            'api_endpoint' => $endpoint,
            'parsing_config' => [
                'selector' => 'div.job-listing',
                'fields' => ['title', 'description', 'requirements'],
                'timeout' => 30,
            ],
            'crawl_delay_seconds' => fake()->numberBetween(1, 10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
