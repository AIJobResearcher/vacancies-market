<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Employer;

interface EmployerRepositoryInterface
{
    public function save(Employer $employer): void;

    public function findById(string $id): ?Employer;

    public function remove(string $id): void;
}
