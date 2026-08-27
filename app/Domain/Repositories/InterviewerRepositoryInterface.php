<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Interviewer;

interface InterviewerRepositoryInterface
{
    public function findById(string $id): ?Interviewer;
    public function save(Interviewer $interviewer): void;
}
