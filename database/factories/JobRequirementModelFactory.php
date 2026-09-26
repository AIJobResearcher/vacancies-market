<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\JobRequirementModel;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<JobRequirementModel>
 */
final class JobRequirementModelFactory extends Factory
{
    /** @var class-string<JobRequirementModel> */
    protected $model = JobRequirementModel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'job_id' => JobModel::factory(),
            'requirement_id' => RequirementModel::factory(),
        ];
    }
}
