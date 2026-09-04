<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\EntityNotFoundException;

use App\Domain\Exceptions\EntityNotFoundException;

final class VacancyNotFoundException extends EntityNotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Vacancy with ID "%s" not found.', $id));
    }
}
