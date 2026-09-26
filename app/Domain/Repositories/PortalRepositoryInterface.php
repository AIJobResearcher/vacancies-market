<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Portal;
use App\Domain\ValueObjects\EntityIds\PortalId;

/** @psalm-suppress UnusedClass */
interface PortalRepositoryInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function findById(PortalId $id): ?Portal;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function save(Portal $portal): void;
}
