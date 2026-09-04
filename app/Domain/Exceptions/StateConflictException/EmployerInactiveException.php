<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\StateConflictException;

use App\Domain\Exceptions\StateConflictException;

final class EmployerInactiveException extends StateConflictException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Employer with ID "%s" is inactive, cannot add vacancy.', $id));
    }
}
