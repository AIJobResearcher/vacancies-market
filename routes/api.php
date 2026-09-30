<?php

declare(strict_types=1);

use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\LocationModel;
use App\Presentation\Http\Controllers\GetJobsByIdsController;
use App\Presentation\Http\Controllers\GetLocationsController;
use App\Presentation\Http\Controllers\GetVacanciesByJobIdController;
use App\Presentation\Http\Controllers\GetVacancyByIdController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function (): array {
        return ['status' => 'ok'];
    });

    // TEMPORARY HACK: POST is kept alongside QUERY until clients migrate.
    // Must be removed once every consumer sends QUERY (OpenAPI documents QUERY only).
    Route::match(['QUERY', 'POST'], '/vacancies', GetVacanciesByJobIdController::class);

    // TEMPORARY HACK: POST is kept alongside QUERY until clients migrate.
    // Must be removed once every consumer sends QUERY (OpenAPI documents QUERY only).
    Route::match(['QUERY', 'POST'], '/jobs', GetJobsByIdsController::class);

    Route::get('/vacancy/{id}', GetVacancyByIdController::class);

    Route::get('/locations', GetLocationsController::class);

    // TEMPORARY HACK: top-3 jobs by the number of vacancies assigned to them.
    // Must live only until the endpoint is implemented in the researcher-crm service,
    // then this route must be removed and the frontend switched to that service.
    Route::get('/researcher', function (): array {
        $jobIds = DB::table('vacancy_job_assignments')
            ->groupBy('job_id')
            ->orderByRaw('count(*) desc')
            ->limit(3)
            ->pluck('job_id');

        $locations = LocationModel::query()
            ->where('iso_name', '=', 'UA')
            ->orWhere('iso_name', '=', 'RU')
            ->get()
            ->pluck('id');

        $jobs = JobModel::query()->whereIn('id', $jobIds)
            ->select(['id'])
            ->get()
            ->map(function ($id) use ($locations) {
                return ['id' => $id->id, 'locations' => $locations];
            });

        return [
            'data' => [
                'id' => Str::uuid()->toString(),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
                'jobs' => $jobs
            ]
        ];
    });
});
