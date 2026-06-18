<?php
declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class Salary
{
    /**
     * Salary value object
     *
     * Use for vacancy salary range. Values are nullable when not provided by
     * source portals. `toArray()` is used for persistence/serialization.
     */
    public ?int $min;
    public ?int $max;
    public string $currency;

    public function __construct(?int $min, ?int $max, string $currency = 'USD')
    {
        if ($min !== null && $max !== null && $min > $max) {
            throw new InvalidArgumentException('Salary min must be <= max');
        }
        $this->min = $min;
        $this->max = $max;
        $this->currency = $currency;
    }

    public function toArray(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
            'currency' => $this->currency,
        ];
    }
}
