<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\InvalidUuidFormatException;
use Ramsey\Uuid\Uuid;

/**
 * @phpstan-consistent-constructor
 */
abstract readonly class EntityId
{
    protected string $value;

    protected function __construct(string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new InvalidUuidFormatException();
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function generate(): static
    {
        return new static(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }
}
