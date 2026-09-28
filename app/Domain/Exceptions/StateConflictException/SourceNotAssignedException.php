<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\StateConflictException;

use App\Domain\Exceptions\StateConflictException;

final class SourceNotAssignedException extends StateConflictException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Source with ID "%s" does not belong to this vacancy.', $id));
    }
}
