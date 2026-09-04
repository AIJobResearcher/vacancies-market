<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class SalaryCurrencyNotAllowedException extends ValidationException
{
    public function __construct(string $currency)
    {
        parent::__construct(sprintf("Currency %s is not allowed. Allowed: USD.", $currency));
    }
}
