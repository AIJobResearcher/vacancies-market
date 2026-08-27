<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Queries;

use App\Application\DTOs\VacancyPreviewData;
use App\Infrastructure\Models\Vacancy as VacancyModel;

final class VacancyPreviewDataMapper
{
    public function map(VacancyModel $model): VacancyPreviewData
    {
        return new VacancyPreviewData(
            id: $model->id,
            title: $model->title,
            employerName: (string) $model->getAttribute('employer_name'),
            salaryMin: $model->salary_min,
            salaryMax: $model->salary_max,
            currency: $model->salary_currency,
            country: $model->country,
            city: $model->city,
            publishedAt: $model->created_at->toIso8601String()
        );
    }
}
