<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enums;

use App\Domain\Enums\WorkplaceEnum;
use PHPUnit\Framework\TestCase;

class WorkplaceEnumTest extends TestCase
{
    public function test_values(): void
    {
        $this->assertSame('remote', WorkplaceEnum::REMOTE->value);
        $this->assertSame('on-site', WorkplaceEnum::ON_SITE->value);
        $this->assertSame('hybrid', WorkplaceEnum::HYBRID->value);
    }

    public function test_from_string(): void
    {
        $this->assertSame(WorkplaceEnum::HYBRID, WorkplaceEnum::from('hybrid'));
    }
}
