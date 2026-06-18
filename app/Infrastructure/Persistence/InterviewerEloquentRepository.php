<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Interviewer;
use App\Domain\Repositories\InterviewerRepositoryInterface;
use App\Domain\ValueObjects\Contacts;
use App\Infrastructure\Models\Interviewer as InterviewerModel;

final class InterviewerEloquentRepository implements InterviewerRepositoryInterface
{
    public function save(Interviewer $interviewer): void
    {
        InterviewerModel::updateOrCreate(
            ['id' => $interviewer->id],
            [
                'employer_id' => $interviewer->employerId,
                'full_name' => $interviewer->fullName,
                'position' => $interviewer->position,
                'email' => $interviewer->email,
                'phone' => $interviewer->contacts->phone,
                'portal_id' => $interviewer->portalId,
                'profile_url' => $interviewer->profileUrl,
                'vacancy_ids' => $interviewer->vacancyIds(),
            ]
        );
    }

    public function findById(string $id): ?Interviewer
    {
        $model = InterviewerModel::find($id);
        if (!$model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findByEmployerId(string $employerId): array
    {
        return InterviewerModel::where('employer_id', $employerId)
            ->get()
            ->map(fn ($model) => $this->toDomain($model))
            ->toArray();
    }

    public function remove(string $id): void
    {
        InterviewerModel::destroy($id);
    }

    private function toDomain(InterviewerModel $model): Interviewer
    {
        $contacts = new Contacts($model->email, $model->phone);
        return new Interviewer(
            $model->id,
            $model->employer_id,
            $model->full_name,
            $model->position,
            $model->email,
            $contacts,
            $model->portal_id,
            $model->profile_url,
            $model->created_at,
            $model->updated_at,
        );
    }
}

