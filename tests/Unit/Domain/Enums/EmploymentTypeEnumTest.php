<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enums;

use App\Domain\Enums\EmploymentTypeEnum;
use PHPUnit\Framework\TestCase;

final class EmploymentTypeEnumTest extends TestCase
{
    public function testValues(): void
    {
        $this->assertSame('part-time', EmploymentTypeEnum::PART_TIME->value);
        $this->assertSame('contract', EmploymentTypeEnum::CONTRACT->value);
        $this->assertSame('internship', EmploymentTypeEnum::INTERNSHIP->value);
        $this->assertSame('full-time', EmploymentTypeEnum::FULL_TIME->value);
        $this->assertSame('volunteer', EmploymentTypeEnum::VOLUNTEER->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(EmploymentTypeEnum::FULL_TIME, EmploymentTypeEnum::from('full-time'));
    }
}
