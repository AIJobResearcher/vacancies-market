<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Interviewer;
use App\Domain\ValueObjects\EntityIds\InterviewerId;

/** @psalm-suppress UnusedClass */
interface InterviewerRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function findById(InterviewerId $id): ?Interviewer;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function save(Interviewer $interviewer): void;
}
