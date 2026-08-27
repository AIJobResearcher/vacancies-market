<?php

declare(strict_types=1);

namespace App\Domain\Services;

interface UuidGeneratorServiceInterface
{
    public function generate(): string;
}