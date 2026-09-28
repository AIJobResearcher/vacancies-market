<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Employer;
use App\Domain\Exceptions\ValidationException\EmployerTitleEmptyException;
use App\Domain\ValueObjects\EmployerContacts;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use Override;
use PHPUnit\Framework\TestCase;

final class EmployerTest extends TestCase
{
    private EmployerId $employerId;

    #[Override]
    protected function setUp(): void
    {
        $this->employerId = EmployerId::generate();
    }

    public function testCreateValid(): void
    {
        $employer = Employer::create(
            $this->employerId,
            'TechCorp',
            'Description',
            EmployerContacts::fromArray([
                ['type' => 'website', 'value' => 'https://techcorp.test'],
                ['type' => 'email', 'value' => 'info@techcorp.test'],
                ['type' => 'phone', 'value' => '+123456789'],
            ]),
            'https://logo.test/logo.png',
            [804, 703448],
        );

        $this->assertEquals('TechCorp', $employer->title());
        $this->assertEquals('Description', $employer->description());
        $this->assertCount(3, $employer->contacts()?->items() ?? []);
        $this->assertEquals('https://logo.test/logo.png', $employer->logoUrl());
        $this->assertEquals([804, 703448], $employer->locationIds());
        $this->assertEquals(1, $employer->version());
    }

    public function testCreateEmptyTitleThrows(): void
    {
        $this->expectException(EmployerTitleEmptyException::class);
        Employer::create($this->employerId, '');
    }
}
