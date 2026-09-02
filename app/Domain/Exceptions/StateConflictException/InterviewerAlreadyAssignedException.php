<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\StateConflictException;

use App\Domain\Exceptions\StateConflictException;

final class InterviewerAlreadyAssignedException extends StateConflictException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Interviewer already assigned to this vacancy. (id %s)', $id));
    }
}