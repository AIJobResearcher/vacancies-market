<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class JobTitleEmptyException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Job title cannot be empty.');
    }
}