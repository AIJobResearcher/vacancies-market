<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enums;

use App\Domain\Enums\VacancyStatusEnum;
use PHPUnit\Framework\TestCase;

class VacancyStatusEnumTest extends TestCase
{
    public function test_values(): void
    {
        $this->assertSame('open', VacancyStatusEnum::OPEN->value);
        $this->assertSame('closed', VacancyStatusEnum::CLOSED->value);
    }

    public function test_from_string(): void
    {
        $this->assertSame(VacancyStatusEnum::CLOSED, VacancyStatusEnum::from('closed'));
    }
}
