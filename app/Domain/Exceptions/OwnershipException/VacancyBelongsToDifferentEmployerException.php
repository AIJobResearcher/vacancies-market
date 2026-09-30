<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\OwnershipException;

use App\Domain\Exceptions\OwnershipException;

final class VacancyBelongsToDifferentEmployerException extends OwnershipException
{
    public function __construct(string $vacancyId, string $employerId)
    {
        parent::__construct(
            sprintf(
                'Vacancy with ID "%s" belongs to a different employer (expected employer: %s).',
                $vacancyId,
                $employerId
            )
        );
    }
}
