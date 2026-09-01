<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\StateConflictException;

use App\Domain\Exceptions\StateConflictException;

final class AssignmentAlreadyInactiveException extends StateConflictException
{
    public function __construct(string $time)
    {
        parent::__construct(sprintf('This assignment is already inactive (unassigned at %s).', $time));
    }
}