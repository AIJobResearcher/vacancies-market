<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class UnknownMutationTypeException extends ValidationException
{
    public function __construct(string $type)
    {
        parent::__construct(sprintf('Unknown mutation type: %s.', $type));
    }
}
