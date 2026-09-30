<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\ContactValueEmptyException;
use App\Domain\Exceptions\ValidationException\InvalidEmailException;

final readonly class Contact
{
    public function __construct(
        private string $type,
        private string $value,
    ) {
        if (trim($value) === '') {
            throw new ContactValueEmptyException($type);
        }

        if ($type === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmailException($value);
        }
    }

    public function type(): string
    {
        return $this->type;
    }

    public function value(): string
    {
        return $this->value;
    }

    /** @return array{type: string, value: string} */
    public function toArray(): array
    {
        return ['type' => $this->type, 'value' => $this->value];
    }
}
