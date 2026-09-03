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
    public static function validUrlsProvider(): array
    {
        return [
            'single' => [['https://example.com'], ['https://example.com']],
            'multiple' => [['https://a.com', 'https://b.com'], ['https://a.com', 'https://b.com']],
            'with duplicates' => [['https://a.com', 'https://a.com', 'https://b.com'], ['https://a.com', 'https://b.com']],
        ];
    }

    public static function invalidUrlsProvider(): array
    {
        return [
            'empty' => [[], ExternalUrlsEmptyException::class],
            'invalid url' => [['not-a-url'], ExternalUrlInvalidException::class],
            'invalid and valid' => [['https://a.com', 'not-a-url'], ExternalUrlInvalidException::class],
        ];
    }

    #[DataProvider('validUrlsProvider')]
    public function test_construct_valid(array $input, array $expected): void
    {
        $urls = new ExternalUrls($input);
        $this->assertEquals($expected, $urls->toArray());
    }

    #[DataProvider('invalidUrlsProvider')]
    public function test_construct_invalid(array $input, string $exceptionClass): void
    {
        $this->expectException($exceptionClass);
        new ExternalUrls($input);
    }

    public function test_equals(): void
    {
        $u1 = new ExternalUrls(['https://a.com']);
        $u2 = new ExternalUrls(['https://a.com']);
        $u3 = new ExternalUrls(['https://b.com']);
        $this->assertTrue($u1->equals($u2));
        $this->assertFalse($u1->equals($u3));
    }

    public function test_is_empty(): void
    {
        $urls = new ExternalUrls(['https://a.com']);
        $this->assertFalse($urls->isEmpty());
    }
}
