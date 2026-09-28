<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\ContactValueEmptyException;
use App\Domain\Exceptions\ValidationException\InvalidEmailException;
use App\Domain\ValueObjects\Contact;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContactTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidEmailProvider(): array
    {
        return [
            'invalid' => ['email', 'invalid'],
            'missing @' => ['email', 'testexample.com'],
        ];
    }

    public function testConstructAndGetters(): void
    {
        $contact = new Contact('website', 'https://example.test');

        $this->assertSame('website', $contact->type());
        $this->assertSame('https://example.test', $contact->value());
    }

    public function testEmptyValueThrows(): void
    {
        $this->expectException(ContactValueEmptyException::class);
        new Contact('phone', '   ');
    }

    #[DataProvider('invalidEmailProvider')]
    public function testInvalidEmailThrows(string $type, string $value): void
    {
        $this->expectException(InvalidEmailException::class);
        new Contact($type, $value);
    }

    public function testNonEmailTypeSkipsEmailValidation(): void
    {
        $contact = new Contact('private-note', 'not-an-email');

        $this->assertSame('not-an-email', $contact->value());
    }

    public function testToArray(): void
    {
        $contact = new Contact('email', 'user@example.test');

        $this->assertSame(['type' => 'email', 'value' => 'user@example.test'], $contact->toArray());
    }
}
