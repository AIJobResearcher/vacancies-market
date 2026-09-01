<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class EmployerTitleEmptyException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Employer title cannot be empty.');
    }
}