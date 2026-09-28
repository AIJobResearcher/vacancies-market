<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\ContactTypeNotAllowedException;
use App\Domain\ValueObjects\Contact;
use App\Domain\ValueObjects\InterviewerContacts;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InterviewerContactsTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function allowedTypeProvider(): array
    {
        return [
            'profile urls' => ['profile_urls'],
            'phone' => ['phone'],
            'email' => ['email'],
        ];
    }

    #[DataProvider('allowedTypeProvider')]
    public function testAllowsDocumentedTypes(string $type): void
    {
        $value = $type === 'email' ? 'user@example.test' : 'https://example.test';

        $contacts = new InterviewerContacts([new Contact($type, $value)]);

        $this->assertCount(1, $contacts->items());
        $this->assertFalse($contacts->isEmpty());
    }

    public function testDisallowedTypeThrows(): void
    {
        $this->expectException(ContactTypeNotAllowedException::class);
        new InterviewerContacts([new Contact('website', 'https://example.test')]);
    }

    public function testFromArrayAndToArray(): void
    {
        $rows = [
            ['type' => 'profile_urls', 'value' => 'https://linkedin.com/in/alice'],
            ['type' => 'email', 'value' => 'alice@example.test'],
        ];

        $contacts = InterviewerContacts::fromArray($rows);

        $this->assertSame($rows, $contacts->toArray());
        $this->assertSame('profile_urls', $contacts->items()[0]->type());
    }
}
