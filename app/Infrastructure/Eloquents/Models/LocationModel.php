<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Models;

use Database\Factories\LocationModelFactory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property string|null $iso_name
 * @property int|null $parent_id
 * @property string $type
 * @property DateTimeImmutable $created_at
 * @property DateTimeImmutable $updated_at
 */
final class LocationModel extends Model
{
    /** @use HasFactory<LocationModelFactory> */
    use HasFactory;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'locations';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $fillable = [
        'id',
        'name',
        'iso_name',
        'parent_id',
        'type',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): LocationModelFactory
    {
        return LocationModelFactory::new();
    }
}
