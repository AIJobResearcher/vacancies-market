<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum EmployerContactTypeEnum: string
{
    case WEBSITE = 'website';
    case PHONE = 'phone';
    case EMAIL = 'email';
}
