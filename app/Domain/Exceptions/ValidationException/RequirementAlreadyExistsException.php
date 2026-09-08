<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class RequirementAlreadyExistsException extends ValidationException
{
    public function __construct(string $title)
    {
        parent::__construct(sprintf('Requirement with title %s already exists.', $title));
    }
}
