<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class MergeListEmptyException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Merged Vacancies list cannot be empty.');
    }
}
