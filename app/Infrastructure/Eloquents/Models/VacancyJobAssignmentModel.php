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
 * @property string $job_id
 * @property DateTimeImmutable $assigned_at
 * @property DateTimeImmutable|null $unassigned_at
 * @property int|null $relevance_score
 * @property int $version
 */
final class VacancyJobAssignmentModel extends Model
{
    use HasUuids;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'vacancy_job_assignments';

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
        'job_id',
        'assigned_at',
        'unassigned_at',
        'relevance_score',
        'version',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'assigned_at' => 'immutable_datetime',
            'unassigned_at' => 'immutable_datetime',
        ];
    }
}
