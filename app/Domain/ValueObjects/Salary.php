<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\SalaryCurrencyNotAllowedException;
use App\Domain\Exceptions\ValidationException\SalaryMaxLessThanMinException;
use App\Domain\Exceptions\ValidationException\SalaryMaxNegativeException;
use App\Domain\Exceptions\ValidationException\SalaryMinNegativeException;

final readonly class Salary
{
    private const array ALLOWED_CURRENCIES = ['USD'];

    public function __construct(
        private int $minSalary,
        private ?int $maxSalary,
        private string $currency = 'USD'
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->minSalary < 0) {
            throw new SalaryMinNegativeException($this->minSalary);
        }
        if ($this->maxSalary !== null && $this->maxSalary < 0) {
            throw new SalaryMaxNegativeException($this->maxSalary);
        }
        if ($this->maxSalary !== null && $this->maxSalary < $this->minSalary) {
            throw new SalaryMaxLessThanMinException($this->minSalary, $this->maxSalary);
        }
        if (!in_array($this->currency, self::ALLOWED_CURRENCIES, true)) {
            throw new SalaryCurrencyNotAllowedException($this->currency);
        }
    }

    public function min(): int
    {
        return $this->minSalary;
    }

    public function max(): ?int
    {
        return $this->maxSalary;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function equals(Salary $other): bool
    {
        return $this->minSalary === $other->minSalary
            && $this->maxSalary === $other->maxSalary
            && $this->currency === $other->currency;
    }
}
