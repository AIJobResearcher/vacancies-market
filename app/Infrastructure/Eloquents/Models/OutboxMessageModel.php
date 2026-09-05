<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
 * @property string $event_id
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property DateTimeImmutable|null $published_at
 * @property int $retry_count
 * @property string|null $last_error
 * @property DateTimeImmutable $created_at
 * @property DateTimeImmutable $updated_at
 */
final class OutboxMessageModel extends Model
{
    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'outbox_messages';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $fillable = [
        'event_id',
        'event_type',
        'payload',
        'published_at',
        'retry_count',
        'last_error',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'published_at' => 'immutable_datetime',
        ];
    }
}
