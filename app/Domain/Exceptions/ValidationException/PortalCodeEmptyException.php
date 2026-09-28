<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class PortalCodeEmptyException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Portal code must not be empty.');
    }
}
