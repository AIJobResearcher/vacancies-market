<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\StateConflictException;

use App\Domain\Exceptions\StateConflictException;

final class NoActiveAssignmentException extends StateConflictException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('No active assignment to this vacancy (%s).', $id));
    }
}