<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enums;

use App\Domain\Enums\WorkplaceEnum;
use PHPUnit\Framework\TestCase;

class WorkplaceEnumTest extends TestCase
{
    public function testValues(): void
    {
        $this->assertSame('remote', WorkplaceEnum::REMOTE->value);
        $this->assertSame('on-site', WorkplaceEnum::ON_SITE->value);
        $this->assertSame('hybrid', WorkplaceEnum::HYBRID->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(WorkplaceEnum::HYBRID, WorkplaceEnum::from('hybrid'));
    }
}
