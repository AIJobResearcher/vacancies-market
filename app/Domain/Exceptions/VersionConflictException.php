<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class VersionConflictException extends DomainException
{
    public function __construct(string $entityType, string $id, int $expected, int $actual)
    {
        parent::__construct(
            sprintf(
                'Version conflict for %s "%s": expected version %d, actual version %d.',
                $entityType,
                $id,
                $expected,
                $actual
            )
        );
    }
}
