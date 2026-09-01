<?php

declare(strict_types=1);

use App\Presentation\Http\Controllers\SearchVacanciesController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function (): array {
        return ['status' => 'ok'];
    });

    Route::middleware(['api.auth', 'api.timing', 'throttle:60,1'])->group(function (): void {
        Route::get('/vacancies', SearchVacanciesController::class);
    });
});
