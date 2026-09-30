<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class VacancyRequiresSourceException extends ValidationException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Vacancy "%s" must have at least one source.', $id));
    }
}
