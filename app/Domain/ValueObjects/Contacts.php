<?php
declare(strict_types=1);

namespace App\Domain\ValueObjects;

final class Contacts
{
    /**
     * Contacts value object
     *
     * Holds basic contact information (email, phone). Use `toArray()` for
     * persistence or event payloads.
     */
    public ?string $email;
    public ?string $phone;

    public function __construct(?string $email = null, ?string $phone = null)
    {
        $this->email = $email;
        $this->phone = $phone;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
