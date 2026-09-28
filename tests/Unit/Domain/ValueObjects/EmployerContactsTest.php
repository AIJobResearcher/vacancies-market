<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\ContactTypeNotAllowedException;
use App\Domain\ValueObjects\Contact;
use App\Domain\ValueObjects\EmployerContacts;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmployerContactsTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function allowedTypeProvider(): array
    {
        return [
            'website' => ['website'],
            'phone' => ['phone'],
            'email' => ['email'],
        ];
    }

    #[DataProvider('allowedTypeProvider')]
    public function testAllowsDocumentedTypes(string $type): void
    {
        $value = $type === 'email' ? 'user@example.test' : 'https://example.test';

        $contacts = new EmployerContacts([new Contact($type, $value)]);

        $this->assertCount(1, $contacts->items());
        $this->assertFalse($contacts->isEmpty());
    }

    public function testDisallowedTypeThrows(): void
    {
        $this->expectException(ContactTypeNotAllowedException::class);
        new EmployerContacts([new Contact('profile_urls', 'https://example.test')]);
    }

    public function testFromArrayAndToArray(): void
    {
        $rows = [
            ['type' => 'website', 'value' => 'https://example.test'],
            ['type' => 'email', 'value' => 'user@example.test'],
        ];

        $contacts = EmployerContacts::fromArray($rows);

        $this->assertSame($rows, $contacts->toArray());
        $this->assertSame('website', $contacts->items()[0]->type());
    }
}
