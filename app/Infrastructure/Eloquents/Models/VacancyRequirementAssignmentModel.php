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
 * @property string $requirement_id
 * @property DateTimeImmutable $assigned_at
 * @property int $version
 */
final class VacancyRequirementAssignmentModel extends Model
{
    use HasUuids;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'vacancy_requirement_assignments';

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
        'requirement_id',
        'assigned_at',
        'version',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'assigned_at' => 'immutable_datetime',
        ];
    }
}
