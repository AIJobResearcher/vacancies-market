<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\ExternalUrlInvalidException;
use App\Domain\Exceptions\ValidationException\ExternalUrlsEmptyException;
use App\Domain\ValueObjects\ExternalUrls;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExternalUrlsTest extends TestCase
{
    /**
     * @return array<string, array{0: string[], 1: string[]}>
     */
    public static function validUrlsProvider(): array
    {
        return [
            'single' => [['https://example.com'], ['https://example.com']],
            'multiple' => [['https://a.com', 'https://b.com'], ['https://a.com', 'https://b.com']],
            'with duplicates' => [
                ['https://a.com', 'https://a.com', 'https://b.com'],
                ['https://a.com', 'https://b.com'],
            ],
        ];
    }

    /**
     * @return array<string, array{0: string[], 1: string}>
     */
    public static function invalidUrlsProvider(): array
    {
        return [
            'empty' => [[], ExternalUrlsEmptyException::class],
            'invalid url' => [['not-a-url'], ExternalUrlInvalidException::class],
            'invalid and valid' => [['https://a.com', 'not-a-url'], ExternalUrlInvalidException::class],
        ];
    }

    /**
     * @param string[] $input
     * @param string[] $expected
     */
    #[DataProvider('validUrlsProvider')]
    public function testConstructValid(array $input, array $expected): void
    {
        $urls = new ExternalUrls($input);
        $this->assertEquals($expected, $urls->toArray());
    }

    /**
     * @param string[] $input
     */
    #[DataProvider('invalidUrlsProvider')]
    public function testConstructInvalid(array $input, string $exceptionClass): void
    {
        $this->expectException($exceptionClass);
        new ExternalUrls($input);
    }

    public function testEquals(): void
    {
        $u1 = new ExternalUrls(['https://a.com']);
        $u2 = new ExternalUrls(['https://a.com']);
        $u3 = new ExternalUrls(['https://b.com']);
        $this->assertTrue($u1->equals($u2));
        $this->assertFalse($u1->equals($u3));
    }

    public function testIsEmpty(): void
    {
        $urls = new ExternalUrls(['https://a.com']);
        $this->assertFalse($urls->isEmpty());
    }
}
