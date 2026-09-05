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
 * @property string $title
 * @property string|null $category
 * @property string|null $sub_category
 * @property string|null $parent_job_id
 * @property string|null $description
 * @property int $version
 * @property DateTimeImmutable|null $deleted_at
 * @property DateTimeImmutable $created_at
 * @property DateTimeImmutable $updated_at
 * @property-read Collection<int, JobRequirementModel> $requirements
 */
final class JobModel extends Model
{
    use HasUuids;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'job_catalogue';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $incrementing = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $keyType = 'string';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $fillable = [
        'id',
        'title',
        'category',
        'sub_category',
        'parent_job_id',
        'description',
        'version',
        'deleted_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'deleted_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<JobRequirementModel,$this>
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(JobRequirementModel::class, 'job_id');
    }
}
