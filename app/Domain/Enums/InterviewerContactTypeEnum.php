<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum InterviewerContactTypeEnum: string
{
    case PROFILE_URLS = 'profile_urls';
    case PHONE = 'phone';
    case EMAIL = 'email';
}
