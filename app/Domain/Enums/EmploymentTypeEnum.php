<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum EmploymentTypeEnum: string
{
    case PART_TIME = 'part-time';
    case CONTRACT = 'contract';
    case INTERNSHIP = 'internship';
    case FULL_TIME = 'full-time';
    case VOLUNTEER = 'volunteer';
}
