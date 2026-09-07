<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\OutboxMessageModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<OutboxMessageModel>
 */
final class OutboxMessageModelFactory extends Factory
{
    /** @var class-string<OutboxMessageModel> */
    protected $model = OutboxMessageModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'event_id' => (string) Str::uuid(),
            'event_type' => 'VacancyImported',
            'payload' => [],
            'published_at' => null,
            'retry_count' => 0,
            'last_error' => null,
        ];
    }
}
