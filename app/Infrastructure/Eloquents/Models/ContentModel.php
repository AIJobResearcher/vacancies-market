<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Models;

use Database\Factories\ContentModelFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $source_id
 * @property string $type
 * @property string $value
 */
final class ContentModel extends Model
{
    /** @use HasFactory<ContentModelFactory> */
    use HasFactory;
    use HasUuids;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'contents';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $incrementing = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $keyType = 'string';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $timestamps = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $fillable = [
        'id',
        'source_id',
        'type',
        'value',
    ];

    protected static function newFactory(): ContentModelFactory
    {
        return ContentModelFactory::new();
    }
}
