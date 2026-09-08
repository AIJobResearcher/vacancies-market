<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Domain\Repositories\EmployerRepositoryInterface;
use App\Domain\Repositories\InterviewerRepositoryInterface;
use App\Domain\Repositories\JobRepositoryInterface;
use App\Domain\Repositories\PortalRepositoryInterface;
use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Infrastructure\Eloquents\Mappers\EmployerMapper;
use App\Infrastructure\Eloquents\Mappers\InterviewerMapper;
use App\Infrastructure\Eloquents\Mappers\JobMapper;
use App\Infrastructure\Eloquents\Mappers\PortalMapper;
use App\Infrastructure\Eloquents\Mappers\RequirementMapper;
use App\Infrastructure\Eloquents\Mappers\VacancyMapper;
use App\Infrastructure\Eloquents\Repositories\EmployerEloquentRepository;
use App\Infrastructure\Eloquents\Repositories\InterviewerEloquentRepository;
use App\Infrastructure\Eloquents\Repositories\JobEloquentRepository;
use App\Infrastructure\Eloquents\Repositories\PortalEloquentRepository;
use App\Infrastructure\Eloquents\Repositories\RequirementEloquentRepository;
use App\Infrastructure\Eloquents\Repositories\VacancyEloquentRepository;
use Illuminate\Support\ServiceProvider;
use Override;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $mappers = [
            VacancyMapper::class,
            EmployerMapper::class,
            JobMapper::class,
            RequirementMapper::class,
            InterviewerMapper::class,
            PortalMapper::class,
        ];

        foreach ($mappers as $mapper) {
            $this->app->singleton($mapper);
        }

        $this->app->singleton(
            VacancyRepositoryInterface::class,
            fn(): VacancyEloquentRepository => new VacancyEloquentRepository(
                $this->app->make(VacancyMapper::class)
            )
        );

        $this->app->singleton(
            EmployerRepositoryInterface::class,
            fn(): EmployerEloquentRepository => new EmployerEloquentRepository(
                $this->app->make(EmployerMapper::class)
            )
        );

        $this->app->singleton(
            JobRepositoryInterface::class,
            fn(): JobEloquentRepository => new JobEloquentRepository(
                $this->app->make(JobMapper::class)
            )
        );

        $this->app->singleton(
            RequirementRepositoryInterface::class,
            fn(): RequirementEloquentRepository => new RequirementEloquentRepository(
                $this->app->make(RequirementMapper::class)
            )
        );

        $this->app->singleton(
            InterviewerRepositoryInterface::class,
            fn(): InterviewerEloquentRepository => new InterviewerEloquentRepository(
                $this->app->make(InterviewerMapper::class)
            )
        );

        $this->app->singleton(
            PortalRepositoryInterface::class,
            fn(): PortalEloquentRepository => new PortalEloquentRepository(
                $this->app->make(PortalMapper::class)
            )
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
