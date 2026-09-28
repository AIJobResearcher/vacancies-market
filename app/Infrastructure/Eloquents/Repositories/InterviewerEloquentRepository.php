<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquents\Repositories;

use App\Domain\Repositories\InterviewerRepositoryInterface;
use App\Infrastructure\Eloquents\Mappers\InterviewerMapper;

final class InterviewerEloquentRepository implements InterviewerRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedProperty */
    public function __construct(private readonly InterviewerMapper $mapper)
    {
    }
}
