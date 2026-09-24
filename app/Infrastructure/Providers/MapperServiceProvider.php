<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Infrastructure\Eloquents\Mappers\EmployerMapper;
use App\Infrastructure\Eloquents\Mappers\InterviewerMapper;
use App\Infrastructure\Eloquents\Mappers\JobMapper;
use App\Infrastructure\Eloquents\Mappers\PortalMapper;
use App\Infrastructure\Eloquents\Mappers\RequirementMapper;
use App\Infrastructure\Eloquents\Mappers\VacancyMapper;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Override;

final class MapperServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /** @var list<class-string> */
    private const array MAPPERS = [
        VacancyMapper::class,
        EmployerMapper::class,
        JobMapper::class,
        RequirementMapper::class,
        InterviewerMapper::class,
        PortalMapper::class,
    ];

    /** @return list<class-string> */
    #[Override]
    public function provides(): array
    {
        return self::MAPPERS;
    }

    #[Override]
    public function register(): void
    {
        foreach (self::MAPPERS as $mapper) {
            $this->app->singleton($mapper);
        }
    }
}
