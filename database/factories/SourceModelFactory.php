<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\PortalModel;
use App\Infrastructure\Eloquents\Models\SourceModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<SourceModel>
 */
final class SourceModelFactory extends Factory
{
    /** @var class-string<SourceModel> */
    protected $model = SourceModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'vacancy_id' => VacancyModel::factory(),
            'portal_id' => PortalModel::factory(),
            'external_vacancy_id' => (string) Str::uuid(),
            'external_url' => fake()->unique()->url(),
            'title' => fake()->jobTitle(),
            'posted_at' => fake()->dateTime(),
        ];
    }
}
