<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Domain\Repositories\EmployerRepositoryInterface;
use App\Domain\Repositories\InterviewerRepositoryInterface;
use App\Domain\Repositories\JobRepositoryInterface;
use App\Domain\Repositories\PortalRepositoryInterface;
use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Infrastructure\Eloquents\Repositories\EmployerEloquentRepository;
use App\Infrastructure\Eloquents\Repositories\InterviewerEloquentRepository;
use App\Infrastructure\Eloquents\Repositories\JobEloquentRepository;
use App\Infrastructure\Eloquents\Repositories\PortalEloquentRepository;
use App\Infrastructure\Eloquents\Repositories\RequirementEloquentRepository;
use App\Infrastructure\Eloquents\Repositories\VacancyEloquentRepository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Override;

final class RepositoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /** @var array<class-string, class-string> */
    private const array REPOSITORIES = [
        VacancyRepositoryInterface::class => VacancyEloquentRepository::class,
        EmployerRepositoryInterface::class => EmployerEloquentRepository::class,
        JobRepositoryInterface::class => JobEloquentRepository::class,
        RequirementRepositoryInterface::class => RequirementEloquentRepository::class,
        InterviewerRepositoryInterface::class => InterviewerEloquentRepository::class,
        PortalRepositoryInterface::class => PortalEloquentRepository::class,
    ];

    /** @return list<class-string> */
    #[Override]
    public function provides(): array
    {
        return array_keys(self::REPOSITORIES);
    }

    #[Override]
    public function register(): void
    {
        foreach (self::REPOSITORIES as $repository => $implementation) {
            $this->app->singleton($repository, $implementation);
        }
    }
}
