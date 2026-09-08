<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\StateConflictException;

use App\Domain\Exceptions\StateConflictException;

final class VacancyNotClosedException extends StateConflictException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Vacancy with ID "%s" is not closed, cannot be removed.', $id));
    }
}
