<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class ExternalUrlInvalidException extends ValidationException
{
    public function __construct(string $url)
    {
        parent::__construct(sprintf('Invalid external URL format: %s.', $url));
    }
}