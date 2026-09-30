<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum LocationTypeEnum: string
{
    case COUNTRY = 'country';
    case CITY = 'city';
    case UNIFICATION_OF_COUNTRIES = 'unification-of-countries';
    case REGION = 'region';
}
