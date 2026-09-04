<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\StateConflictException;

use App\Domain\Exceptions\StateConflictException;

final class JobNotAssignedException extends StateConflictException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Vacancy is not currently assigned to Job with ID "%s".', $id));
    }
}
