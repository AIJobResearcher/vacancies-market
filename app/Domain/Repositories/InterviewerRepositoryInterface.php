<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Interviewer;

interface InterviewerRepositoryInterface
{
    public function save(Interviewer $interviewer): void;

    public function findById(string $id): ?Interviewer;

    /** @return Interviewer[] */
    public function findByEmployerId(string $employerId): array;

    public function remove(string $id): void;
}
