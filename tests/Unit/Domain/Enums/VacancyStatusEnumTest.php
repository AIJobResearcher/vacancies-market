<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enums;

use App\Domain\Enums\VacancyStatusEnum;
use PHPUnit\Framework\TestCase;

class VacancyStatusEnumTest extends TestCase
{
    public function testValues(): void
    {
        $this->assertSame('open', VacancyStatusEnum::OPEN->value);
        $this->assertSame('closed', VacancyStatusEnum::CLOSED->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(VacancyStatusEnum::CLOSED, VacancyStatusEnum::from('closed'));
    }
}
