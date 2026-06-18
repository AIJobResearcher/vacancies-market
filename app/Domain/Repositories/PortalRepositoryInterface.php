++<?php
declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Portal;

interface PortalRepositoryInterface
{
    public function save(Portal $portal): void;

    public function findById(string $id): ?Portal;

    /** @return Portal[] */
    public function all(): array;

    public function remove(string $id): void;
}
