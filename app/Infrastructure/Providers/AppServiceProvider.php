<?php

namespace App\Infrastructure\Providers;

use App\Domain\Repositories\EmployerRepositoryInterface;
use App\Domain\Repositories\InterviewerRepositoryInterface;
use App\Domain\Repositories\PortalRepositoryInterface;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Infrastructure\Persistence\EmployerEloquentRepository;
use App\Infrastructure\Persistence\InterviewerEloquentRepository;
use App\Infrastructure\Persistence\PortalEloquentRepository;
use App\Infrastructure\Persistence\VacancyEloquentRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            EmployerRepositoryInterface::class,
            EmployerEloquentRepository::class
        );

        $this->app->singleton(
            VacancyRepositoryInterface::class,
            VacancyEloquentRepository::class
        );

        $this->app->singleton(
            InterviewerRepositoryInterface::class,
            InterviewerEloquentRepository::class
        );

        $this->app->singleton(
            PortalRepositoryInterface::class,
            PortalEloquentRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
