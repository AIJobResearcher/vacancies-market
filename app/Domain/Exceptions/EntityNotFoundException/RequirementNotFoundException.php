<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\EntityNotFoundException;

use App\Domain\Exceptions\EntityNotFoundException;

final class RequirementNotFoundException extends EntityNotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Requirement with ID "%s" not found.', $id));
    }
}
