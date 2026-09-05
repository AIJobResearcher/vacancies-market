<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Entities\Interviewer;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\InterviewerRepositoryInterface;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Infrastructure\Eloquents\Mappers\InterviewerMapper;
use App\Infrastructure\Eloquents\Models\InterviewerModel;
use App\Infrastructure\Eloquents\Models\InterviewerVacancyAssignmentModel;
use Illuminate\Support\Facades\DB;
use Override;

final class InterviewerEloquentRepository implements InterviewerRepositoryInterface
{
    public function __construct(private readonly InterviewerMapper $mapper)
    {
    }

    #[Override]
    public function findById(InterviewerId $id): ?Interviewer
    {
        $model = InterviewerModel::query()
            ->with('vacancyAssignments')
            ->find($id->value());

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    #[Override]
    public function save(Interviewer $interviewer): void
    {
        DB::transaction(function () use ($interviewer): void {
            $this->persistRoot($interviewer);
            $this->reconcileVacancyAssignments($interviewer);
        });
    }

    private function persistRoot(Interviewer $interviewer): void
    {
        $state = $this->mapper->toPersistenceState($interviewer);

        if (InterviewerModel::query()->whereKey($interviewer->id()->value())->exists()) {
            $expected = $interviewer->version() - 1;
            $affected = InterviewerModel::query()
                ->whereKey($interviewer->id()->value())
                ->where('version', $expected)
                ->update($state);

            if ($affected === 0) {
                $existing = InterviewerModel::query()
                    ->whereKey($interviewer->id()->value())
                    ->first(['version']);

                $actual = $existing === null ? 0 : $existing->version;

                throw new VersionConflictException(
                    'Interviewer',
                    $interviewer->id()->value(),
                    $expected,
                    $actual,
                );
            }
        } else {
            InterviewerModel::query()->create($state);
        }
    }

    private function reconcileVacancyAssignments(Interviewer $interviewer): void
    {
        $interviewerId = $interviewer->id()->value();
        $snapshot = $interviewer->getVacancyAssignments();

        InterviewerVacancyAssignmentModel::query()
            ->where('interviewer_id', $interviewerId)
            ->whereNotIn('id', array_map(fn ($a): string => $a->id()->value(), $snapshot))
            ->delete();

        foreach ($snapshot as $assignment) {
            $values = [
                'interviewer_id' => $assignment->interviewerId()->value(),
                'vacancy_id' => $assignment->vacancyId()->value(),
                'assigned_at' => $assignment->assignedAt(),
                'unassigned_at' => $assignment->unassignedAt(),
                'version' => $assignment->version(),
            ];

            InterviewerVacancyAssignmentModel::query()->updateOrCreate(
                ['id' => $assignment->id()->value()],
                $values,
            );
        }
    }
}
