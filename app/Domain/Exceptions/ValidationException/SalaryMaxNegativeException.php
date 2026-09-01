<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class SalaryMaxNegativeException extends ValidationException
{
    public function __construct(int $max)
    {
        parent::__construct(sprintf('Maximum salary cannot be negative (given: %d).', $max));
    }
}