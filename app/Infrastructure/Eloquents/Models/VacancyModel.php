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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Override;

/**
 * @property string $id
 * @property string $employer_id
 * @property string $title
 * @property string|null $description
 * @property int $min_salary
 * @property int|null $max_salary
 * @property string $salary_currency
 * @property string $status
 * @property string|null $country
 * @property string|null $city
 * @property string $employment_type
 * @property string $workplace
 * @property DateTimeImmutable $posted_at
 * @property DateTimeImmutable|null $closed_at
 * @property DateTimeImmutable $created_at
 * @property DateTimeImmutable $updated_at
 * @property int $version
 * @property list<string> $external_urls
 * @property string|null $internal_url
 * @property-read Collection<int, VacancyRequirementAssignmentModel> $requirementAssignments
 * @property-read Collection<int, VacancyJobAssignmentModel> $jobAssignments
 * @property-read Collection<int, VacancySourceModel> $sources
 * @property-read EmployerModel $employer
 * @property-read Collection<int, RequirementModel> $requirements
 * @property-read InterviewerVacancyAssignmentModel|null $activeAssignment
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
        'description',
        'min_salary',
        'max_salary',
        'salary_currency',
        'status',
        'country',
        'city',
        'employment_type',
        'workplace',
        'posted_at',
        'closed_at',
        'version',
        'external_urls',
        'internal_url',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'posted_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'external_urls' => 'array',
        ];
    }

    protected static function newFactory(): VacancyModelFactory
    {
        return VacancyModelFactory::new();
    }

    /**
     * @return HasMany<VacancyRequirementAssignmentModel,$this>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function requirementAssignments(): HasMany
    {
        return $this->hasMany(VacancyRequirementAssignmentModel::class, 'vacancy_id');
    }

    /**
     * @return HasMany<VacancyJobAssignmentModel,$this>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function jobAssignments(): HasMany
    {
        return $this->hasMany(VacancyJobAssignmentModel::class, 'vacancy_id');
    }

    /**
     * @return HasMany<VacancySourceModel,$this>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function sources(): HasMany
    {
        return $this->hasMany(VacancySourceModel::class, 'vacancy_id');
    }

    /**
     * @return BelongsTo<EmployerModel,$this>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerModel::class, 'employer_id');
    }

    /**
     * @return BelongsToMany<RequirementModel,$this,Pivot,'pivot'>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function requirements(): BelongsToMany
    {
        $relation = $this->belongsToMany(
            RequirementModel::class,
            'vacancy_requirement_assignments',
            'vacancy_id',
            'requirement_id',
        );

        $relation->orderBy('requirements.title');

        return $relation;
    }

    /**
     * @return HasOne<InterviewerVacancyAssignmentModel,$this>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function activeAssignment(): HasOne
    {
        $relation = $this->hasOne(InterviewerVacancyAssignmentModel::class, 'vacancy_id');

        $relation->whereNull('unassigned_at');
        $relation->orderByDesc('assigned_at');

        return $relation;
    }
}
