<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Models;

use Database\Factories\VacancyModelFactory;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Override;

/**
 * @property string $id
 * @property string $employer_id
 * @property string $title
 * @property int $min_salary
 * @property int|null $max_salary
 * @property string $status
 * @property list<string> $employment_types
 * @property list<string> $workplaces
 * @property list<int> $researcher_location_ids
 * @property DateTimeImmutable|null $closed_at
 * @property DateTimeImmutable $created_at
 * @property DateTimeImmutable $updated_at
 * @property int $version
 * @property-read Collection<int, RequirementModel> $requirements
 * @property-read Collection<int, JobModel> $jobs
 * @property-read Collection<int, SourceModel> $sources
 * @property-read EmployerModel $employer
 * @property-read string $employer_title Present when the query selects it
 *     through the `employers.title as employer_title` alias (9.2)
 */
final class VacancyModel extends Model
{
    /** @use HasFactory<VacancyModelFactory> */
    use HasFactory;
    use HasUuids;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'vacancies';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $incrementing = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $keyType = 'string';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $fillable = [
        'id',
        'employer_id',
        'title',
        'min_salary',
        'max_salary',
        'status',
        'employment_types',
        'workplaces',
        'researcher_location_ids',
        'closed_at',
        'version',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'employment_types' => 'array',
            'workplaces' => 'array',
            'researcher_location_ids' => 'array',
            'closed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): VacancyModelFactory
    {
        return VacancyModelFactory::new();
    }

    /**
     * @return BelongsToMany<RequirementModel,$this,Pivot,'pivot'>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function requirements(): BelongsToMany
    {
        return $this->belongsToMany(
            RequirementModel::class,
            'vacancy_requirement_assignments',
            'vacancy_id',
            'requirement_id',
        );
    }

    /**
     * @return BelongsToMany<JobModel,$this,Pivot,'pivot'>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(
            JobModel::class,
            'vacancy_job_assignments',
            'vacancy_id',
            'job_id',
        );
    }

    /**
     * @return HasMany<SourceModel,$this>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function sources(): HasMany
    {
        return $this->hasMany(SourceModel::class, 'vacancy_id');
    }

    /**
     * @return BelongsTo<EmployerModel,$this>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerModel::class, 'employer_id');
    }
}
