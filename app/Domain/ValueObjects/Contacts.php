<?php
declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException\InvalidEmailException;

final readonly class Contacts
{
    public ?string $email;
    public ?string $phone;

    public function __construct(
        ?string $email = null,
        ?string $phone = null
    ) {
        $this->email = $this->validateEmail($email);
        $this->phone = $phone;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }

    private function validateEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException($email);
        }

        return $email;
    }
}