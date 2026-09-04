<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class ExternalUrlsEmptyException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('At least one external URL is required for a vacancy.');
    }
}
