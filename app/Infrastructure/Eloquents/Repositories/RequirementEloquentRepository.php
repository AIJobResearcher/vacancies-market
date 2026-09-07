<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Entities\Requirement;
use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Infrastructure\Eloquents\Mappers\RequirementMapper;
use App\Infrastructure\Eloquents\Models\JobRequirementModel;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use App\Infrastructure\Eloquents\Models\VacancyRequirementAssignmentModel;
use Override;

final class RequirementEloquentRepository implements RequirementRepositoryInterface
{
    public function __construct(private readonly RequirementMapper $mapper)
    {
    }

    #[Override]
    public function findById(RequirementId $id): ?Requirement
    {
        $model = RequirementModel::query()->find($id->value());

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    #[Override]
    public function findByTitleCaseInsensitive(string $title): ?Requirement
    {
        $model = RequirementModel::query()
            ->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    #[Override]
    public function save(Requirement $requirement): void
    {
        RequirementModel::query()->updateOrCreate(
            ['id' => $requirement->id()->value()],
            $this->mapper->toPersistenceState($requirement),
        );
    }

    #[Override]
    public function isReferencedByActiveVacancyOrJob(RequirementId $requirementId): bool
    {
        $requirementIdValue = $requirementId->value();

        $byActiveVacancy = VacancyRequirementAssignmentModel::query()
            ->join('vacancies', 'vacancies.id', '=', 'vacancy_requirement_assignments.vacancy_id')
            ->where('vacancy_requirement_assignments.requirement_id', $requirementIdValue)
            ->where('vacancies.status', 'open')
            ->exists();

        if ($byActiveVacancy) {
            return true;
        }

        return JobRequirementModel::query()
            ->join('job_catalogue', 'job_catalogue.id', '=', 'job_requirements.job_id')
            ->where('job_requirements.requirement_id', $requirementIdValue)
            ->whereNull('job_catalogue.deleted_at')
            ->exists();
    }
}
