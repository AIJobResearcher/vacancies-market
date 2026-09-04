<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Interviewer;
use App\Domain\ValueObjects\EntityIds\InterviewerId;

interface InterviewerRepositoryInterface
{
    public function findById(InterviewerId $id): ?Interviewer;
    public function save(Interviewer $interviewer): void;
}
