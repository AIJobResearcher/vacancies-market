<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class InvalidEmailException extends ValidationException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('Invalid email address: %s', $email));
    }
}
