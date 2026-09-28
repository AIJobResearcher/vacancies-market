<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Models;

use Database\Factories\InterviewerModelFactory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string $id
 * @property string $employer_id
 * @property string $full_name
 * @property string|null $position
 * @property list<array{type: string, value: string}>|null $contacts
 * @property string|null $avatar_url
 * @property bool $is_active
 * @property int $version
 * @property DateTimeImmutable|null $deleted_at
 * @property DateTimeImmutable $created_at
 * @property DateTimeImmutable $updated_at
 */
final class InterviewerModel extends Model
{
    /** @use HasFactory<InterviewerModelFactory> */
    use HasFactory;
    use HasUuids;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'interviewers';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $incrementing = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $keyType = 'string';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $fillable = [
        'id',
        'employer_id',
        'full_name',
        'position',
        'contacts',
        'avatar_url',
        'is_active',
        'version',
        'deleted_at',
    ];

    protected static function newFactory(): InterviewerModelFactory
    {
        return InterviewerModelFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'contacts' => 'array',
            'is_active' => 'boolean',
            'deleted_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
