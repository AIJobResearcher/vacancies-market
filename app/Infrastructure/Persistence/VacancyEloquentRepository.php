<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Vacancy;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Domain\ValueObjects\Salary;
use App\Infrastructure\Models\Vacancy as VacancyModel;

final class VacancyEloquentRepository implements VacancyRepositoryInterface
{
    public function save(Vacancy $vacancy): void
    {
        VacancyModel::updateOrCreate(
            ['id' => $vacancy->id],
            [
                'employer_id' => $vacancy->employerId,
                'title' => $vacancy->title,
                'description' => $vacancy->description,
                'requirements' => $vacancy->requirements,
                'salary_min' => $vacancy->salary?->min,
                'salary_max' => $vacancy->salary?->max,
                'salary_currency' => $vacancy->salary?->currency ?? 'USD',
                'status' => $vacancy->status->value,
                'country' => $vacancy->country,
                'city' => $vacancy->city,
                'version' => $vacancy->version,
            ]
        );
    }

    public function findById(string $id): ?Vacancy
    {
        $model = VacancyModel::find($id);
        if (! $model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findByEmployerId(string $employerId): array
    {
        return VacancyModel::where('employer_id', $employerId)
            ->get()
            ->map(fn ($model) => $this->toDomain($model))
            ->toArray();
    }

    public function remove(string $id): void
    {
        VacancyModel::destroy($id);
    }

    private function toDomain(VacancyModel $model): Vacancy
    {
        $salary = null;
        if ($model->salary_min !== null || $model->salary_max !== null) {
            $salary = new Salary($model->salary_min, $model->salary_max, $model->salary_currency);
        }

        return new Vacancy(
            $model->id,
            $model->employer_id,
            $model->title,
            $model->description,
            $model->requirements ?? [],
            $salary,
            VacancyStatusEnum::from($model->status),
            $model->country,
            $model->city,
            $model->created_at,
            $model->updated_at,
            $model->version,
        );
    }
}
