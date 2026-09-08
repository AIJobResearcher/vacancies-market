<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\StateConflictException;

use App\Domain\Exceptions\StateConflictException;

final class RequirementNotAssignedException extends StateConflictException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Requirement with ID "%s" is not assigned to this vacancy.', $id));
    }
}
