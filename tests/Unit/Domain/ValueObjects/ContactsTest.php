<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\InvalidEmailException;
use App\Domain\ValueObjects\Contacts;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContactsTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function validContactsProvider(): array
    {
        return [
            'with email and phone' => ['test@example.com', '+123456789'],
            'only email' => ['test@example.com', null],
            'only phone' => [null, '+123'],
            'both null' => [null, null],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidEmailProvider(): array
    {
        return [
            'invalid' => ['invalid'],
            'empty' => [''],
            'missing @' => ['testexample.com'],
        ];
    }

    #[DataProvider('validContactsProvider')]
    public function testConstructValid(?string $email, ?string $phone): void
    {
        $contacts = new Contacts($email, $phone);
        $this->assertEquals($email, $contacts->email);
        $this->assertEquals($phone, $contacts->phone);
    }

    #[DataProvider('invalidEmailProvider')]
    public function testInvalidEmailThrows(string $invalid): void
    {
        $this->expectException(InvalidEmailException::class);
        new Contacts($invalid);
    }

    public function testToArray(): void
    {
        $contacts = new Contacts('test@example.com', '+123');
        $this->assertEquals(['email' => 'test@example.com', 'phone' => '+123'], $contacts->toArray());
    }
}
