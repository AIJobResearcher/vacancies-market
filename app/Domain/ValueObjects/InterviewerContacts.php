<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Enums\InterviewerContactTypeEnum;
use App\Domain\Exceptions\ValidationException\ContactTypeNotAllowedException;

final readonly class InterviewerContacts
{
    /** @param list<Contact> $items */
    public function __construct(private array $items = [])
    {
        foreach ($items as $item) {
            if (InterviewerContactTypeEnum::tryFrom($item->type()) === null) {
                throw new ContactTypeNotAllowedException($item->type());
            }
        }
    }

    /** @param list<array{type: string, value: string}> $rows */
    public static function fromArray(array $rows): self
    {
        return new self(array_map(
            static fn (array $row): Contact => new Contact($row['type'], $row['value']),
            $rows,
        ));
    }

    /** @return list<Contact> */
    public function items(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** @return list<array{type: string, value: string}> */
    public function toArray(): array
    {
        return array_map(
            static fn (Contact $contact): array => $contact->toArray(),
            $this->items,
        );
    }
}
