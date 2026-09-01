<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class SalaryMaxLessThanMinException extends ValidationException
{
    public function __construct(int $max, int $min)
    {
        parent::__construct(
            sprintf(
                'Maximum salary (%d) must be greater than or equal to minimum salary (%d).',
                $max,
                $min
            )
        );
    }
}