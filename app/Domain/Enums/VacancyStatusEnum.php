<?php

declare(strict_types=1);

namespace App\Domain\Enums;

/**
 * Vacancy status enum
 *
 * Use this enum to represent vacancy lifecycle states instead of raw strings.
 */
enum VacancyStatusEnum: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
}
