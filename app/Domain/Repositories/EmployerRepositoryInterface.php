<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Employer;
use App\Domain\ValueObjects\EntityIds\EmployerId;

interface EmployerRepositoryInterface
{
    public function findById(EmployerId $id): ?Employer;
    public function save(Employer $employer): void;
}