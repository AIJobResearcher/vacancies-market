<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\OwnershipException;

use App\Domain\Exceptions\OwnershipException;

final class InterviewerVacancyEmployerMismatchException extends OwnershipException
{
    public function __construct()
    {
        parent::__construct('Interviewer and vacancy belong to different employers.');
    }
}
