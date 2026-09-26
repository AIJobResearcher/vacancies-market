<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\OwnershipException;

use App\Domain\Exceptions\OwnershipException;

final class InterviewerBelongsToDifferentEmployerException extends OwnershipException
{
    public function __construct(string $interviewerId, string $employerId)
    {
        parent::__construct(
            sprintf(
                'Interviewer with ID "%s" belongs to a different employer (expected employer: %s).',
                $interviewerId,
                $employerId
            )
        );
    }
}
