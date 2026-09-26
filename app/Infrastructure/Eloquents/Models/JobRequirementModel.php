<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Models;

use Database\Factories\JobRequirementModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $job_id
 * @property string $requirement_id
 */
final class JobRequirementModel extends Model
{
    /** @use HasFactory<JobRequirementModelFactory> */
    use HasFactory;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $table = 'job_requirements';

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $incrementing = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    public $timestamps = false;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint */
    protected $fillable = [
        'job_id',
        'requirement_id',
    ];

    protected static function newFactory(): JobRequirementModelFactory
    {
        return JobRequirementModelFactory::new();
    }
}
