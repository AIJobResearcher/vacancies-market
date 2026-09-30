<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class ContactValueEmptyException extends ValidationException
{
    public function __construct(string $type)
    {
        parent::__construct(sprintf('Contact value must not be empty for type: %s.', $type));
    }
}
