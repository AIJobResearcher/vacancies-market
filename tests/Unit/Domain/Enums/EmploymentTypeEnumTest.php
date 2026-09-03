<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enums;

use App\Domain\Enums\EmploymentTypeEnum;
use PHPUnit\Framework\TestCase;

class EmploymentTypeEnumTest extends TestCase
{
    public function test_values(): void
    {
        $this->assertSame('part-time', EmploymentTypeEnum::PART_TIME->value);
        $this->assertSame('contract', EmploymentTypeEnum::CONTRACT->value);
        $this->assertSame('internship', EmploymentTypeEnum::INTERNSHIP->value);
        $this->assertSame('full-time', EmploymentTypeEnum::FULL_TIME->value);
        $this->assertSame('volunteer', EmploymentTypeEnum::VOLUNTEER->value);
    }

    public function test_from_string(): void
    {
        $this->assertSame(EmploymentTypeEnum::FULL_TIME, EmploymentTypeEnum::from('full-time'));
    }
}
