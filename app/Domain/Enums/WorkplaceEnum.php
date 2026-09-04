<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum WorkplaceEnum: string
{
    case REMOTE = 'remote';
    case ON_SITE = 'on-site';
    case HYBRID = 'hybrid';
}
