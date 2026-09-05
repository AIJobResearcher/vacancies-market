<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string $id
 * @property string $vacancy_id
 * @property string $source_key
 * @property string $external_vacancy_id
 * @property string $external_url
 * @property DateTimeImmutable $first_seen_at
 * @property DateTimeImmutable $last_seen_at
 * @property DateTimeImmutable|null $closed_at
 * @property bool $is_primary
 */
final class VacancySourceModel extends Model
{
    use HasUuids;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'vacancy_sources';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $incrementing = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $keyType = 'string';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $timestamps = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $fillable = [
        'id',
        'vacancy_id',
        'source_key',
        'external_vacancy_id',
        'external_url',
        'first_seen_at',
        'last_seen_at',
        'closed_at',
        'is_primary',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'first_seen_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'is_primary' => 'boolean',
        ];
    }
}
