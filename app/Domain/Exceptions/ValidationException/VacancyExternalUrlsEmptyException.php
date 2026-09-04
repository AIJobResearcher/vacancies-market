<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class VacancyExternalUrlsEmptyException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Vacancy must have at least one external URL.');
    }
}
