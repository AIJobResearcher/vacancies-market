<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Models;

use Database\Factories\PortalModelFactory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string $id
 * @property string $name
 * @property string $base_url
 * @property string|null $api_endpoint
 * @property int $crawl_delay_seconds
 * @property int $version
 * @property DateTimeImmutable $created_at
 * @property DateTimeImmutable $updated_at
 */
final class PortalModel extends Model
{
    /** @use HasFactory<PortalModelFactory> */
    use HasFactory;
    use HasUuids;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'portals';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $incrementing = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $keyType = 'string';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $fillable = [
        'id',
        'name',
        'base_url',
        'api_endpoint',
        'crawl_delay_seconds',
        'version',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): PortalModelFactory
    {
        return PortalModelFactory::new();
    }
}
