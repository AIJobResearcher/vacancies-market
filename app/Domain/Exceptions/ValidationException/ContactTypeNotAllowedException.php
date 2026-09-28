<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class ContactTypeNotAllowedException extends ValidationException
{
    public function __construct(string $type)
    {
        parent::__construct(sprintf('Contact type is not allowed: %s.', $type));
    }
}
