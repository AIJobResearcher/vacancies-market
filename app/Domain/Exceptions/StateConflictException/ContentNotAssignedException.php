<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\StateConflictException;

use App\Domain\Exceptions\StateConflictException;

final class ContentNotAssignedException extends StateConflictException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Content with ID "%s" does not belong to this source.', $id));
    }
}
