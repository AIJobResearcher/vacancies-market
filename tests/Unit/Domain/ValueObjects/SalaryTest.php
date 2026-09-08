<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\SalaryCurrencyNotAllowedException;
use App\Domain\Exceptions\ValidationException\SalaryMaxLessThanMinException;
use App\Domain\Exceptions\ValidationException\SalaryMaxNegativeException;
use App\Domain\Exceptions\ValidationException\SalaryMinNegativeException;
use App\Domain\ValueObjects\Salary;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SalaryTest extends TestCase
{
    /**
     * @return array<string, array{
     *     0: int,
     *     1: int|null,
     *     2: string|null,
     *     3: int,
     *     4: int|null,
     *     5?: string
     * }>
     */
    public static function validSalaryProvider(): array
    {
        return [
            'min only' => [1500, null, 'USD', 1500, null],
            'min and max' => [1000, 2000, 'USD', 1000, 2000],
            'zero min' => [0, 1000, 'USD', 0, 1000],
            'default currency' => [1000, 2000, null, 1000, 2000, 'USD'],
        ];
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: string, 3: string}>
     */
    public static function invalidSalaryProvider(): array
    {
        return [
            'min negative' => [-10, 1000, 'USD', SalaryMinNegativeException::class],
            'max negative' => [1000, -500, 'USD', SalaryMaxNegativeException::class],
            'max < min' => [2000, 1500, 'USD', SalaryMaxLessThanMinException::class],
            'invalid currency' => [1000, 2000, 'EUR', SalaryCurrencyNotAllowedException::class],
        ];
    }

    #[DataProvider('validSalaryProvider')]
    public function testConstructValid(
        int $min,
        ?int $max,
        ?string $currency,
        int $expectedMin,
        ?int $expectedMax,
        string $expectedCurrency = 'USD'
    ): void {
        $salary = $currency === null
            ? new Salary($min, $max)
            : new Salary($min, $max, $currency);
        $this->assertEquals($expectedMin, $salary->min());
        $this->assertEquals($expectedMax, $salary->max());
        $this->assertEquals($expectedCurrency, $salary->currency());
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('invalidSalaryProvider')]
    public function testConstructInvalid(int $min, ?int $max, string $currency, string $exceptionClass): void
    {
        $this->expectException($exceptionClass);
        new Salary($min, $max, $currency);
    }

    public function testEquals(): void
    {
        $s1 = new Salary(1000, 2000);
        $s2 = new Salary(1000, 2000);
        $s3 = new Salary(1500, 2000);
        $this->assertTrue($s1->equals($s2));
        $this->assertFalse($s1->equals($s3));
    }
}
