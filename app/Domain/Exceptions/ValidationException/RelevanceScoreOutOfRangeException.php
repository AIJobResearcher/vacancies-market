<?php

declare(strict_types=1);

namespace App\Domain\Exceptions\ValidationException;

use App\Domain\Exceptions\ValidationException;

final class RelevanceScoreOutOfRangeException extends ValidationException
{
    public function __construct(int $score)
    {
        parent::__construct(sprintf('Relevance score must be between 1 and 100 (given: %d).', $score));
    }
}