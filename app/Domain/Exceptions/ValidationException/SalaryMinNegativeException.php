<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class SalaryMinNegativeException extends ValidationException
{
    public function __construct(int $min)
    {
        parent::__construct(sprintf('Minimum salary cannot be negative (given: %d).', $min));
    }
}