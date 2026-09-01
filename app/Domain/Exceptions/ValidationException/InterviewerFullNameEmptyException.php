<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class InterviewerFullNameEmptyException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Interviewer full name cannot be empty.');
    }
}