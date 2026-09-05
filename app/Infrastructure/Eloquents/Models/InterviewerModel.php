<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string $id
 * @property string $employer_id
 * @property string $full_name
 * @property string|null $position
 * @property array<string, string>|null $profile_urls
 * @property bool $is_active
 * @property int $version
 * @property DateTimeImmutable|null $deleted_at
 * @property DateTimeImmutable $created_at
 * @property DateTimeImmutable $updated_at
 * @property-read Collection<int, InterviewerVacancyAssignmentModel> $vacancyAssignments
 */
final class InterviewerModel extends Model
{
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
        'profile_urls',
        'is_active',
        'version',
        'deleted_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'profile_urls' => 'array',
            'is_active' => 'boolean',
            'deleted_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<InterviewerVacancyAssignmentModel,$this>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function vacancyAssignments(): HasMany
    {
        return $this->hasMany(InterviewerVacancyAssignmentModel::class, 'interviewer_id');
    }
}
