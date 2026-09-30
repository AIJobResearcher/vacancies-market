<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class LocationNameEmptyException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Location name must not be empty.');
    }
}
