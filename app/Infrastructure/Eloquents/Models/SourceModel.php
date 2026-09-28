<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Models;

use Database\Factories\SourceModelFactory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string $id
 * @property string $vacancy_id
 * @property string $portal_id
 * @property string|null $external_vacancy_id
 * @property string $external_url
 * @property string $title
 * @property DateTimeImmutable $posted_at
 * @property DateTimeImmutable $created_at
 * @property DateTimeImmutable $updated_at
 * @property-read Collection<int, ContentModel> $contents
 */
final class SourceModel extends Model
{
    /** @use HasFactory<SourceModelFactory> */
    use HasFactory;
    use HasUuids;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'sources';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $incrementing = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $keyType = 'string';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $fillable = [
        'id',
        'vacancy_id',
        'portal_id',
        'external_vacancy_id',
        'external_url',
        'title',
        'posted_at',
    ];

    protected static function newFactory(): SourceModelFactory
    {
        return SourceModelFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'posted_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<ContentModel,$this>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function contents(): HasMany
    {
        return $this->hasMany(ContentModel::class, 'source_id');
    }
}
