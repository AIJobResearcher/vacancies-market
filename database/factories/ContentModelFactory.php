<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Enums\ContentTypeEnum;
use App\Infrastructure\Eloquents\Models\ContentModel;
use App\Infrastructure\Eloquents\Models\SourceModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<ContentModel>
 */
final class ContentModelFactory extends Factory
{
    /** @var class-string<ContentModel> */
    protected $model = ContentModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'source_id' => SourceModel::factory(),
            'type' => ContentTypeEnum::DESCRIPTION->value,
            'value' => fake()->paragraph(random_int(6, 13)),
        ];
    }
}
