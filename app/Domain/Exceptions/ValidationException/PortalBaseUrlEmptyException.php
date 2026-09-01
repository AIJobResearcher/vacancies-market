<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class PortalBaseUrlEmptyException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Portal baseUrl must not be empty.');
    }
}