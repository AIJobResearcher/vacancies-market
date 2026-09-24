<?php

declare(strict_types=1);

use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\VacancyJobAssignmentModel;
use App\Presentation\Http\Controllers\GetVacanciesByJobIdController;
use App\Presentation\Http\Controllers\GetVacancyByIdController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function (): array {
        return ['status' => 'ok'];
    });

    Route::post('/vacancies', GetVacanciesByJobIdController::class);

    Route::get('/vacancy/{id}', GetVacancyByIdController::class);

    // TEMPORARY HACK: top-3 jobs by the number of vacancies assigned to them.
    // Must live only until the endpoint is implemented in the researcher-crm service,
    // then this route must be removed and the frontend switched to that service.
    Route::get('/jobs', function (): array {
        $jobIds = VacancyJobAssignmentModel::query()
            ->whereNull('unassigned_at')
            ->groupBy('job_id')
            ->orderByRaw('count(*) desc')
            ->limit(3)
            ->pluck('job_id');

        $jobs = JobModel::query()->whereIn('id', $jobIds)
            ->select(['id', 'title', 'category', 'sub_category', 'parent_job_id'])
            ->get()
            ->toArray();

        $jobs[0] = $jobs[0] + ['parent_job_title'=> 'Parent job title'];

        return ['data' => $jobs];
    });
});
