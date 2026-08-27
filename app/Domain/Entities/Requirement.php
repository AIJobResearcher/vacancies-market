<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\InvalidOperationException;
use DateTimeImmutable;

final class Requirement
{
    private function __construct(
        private string $id,
        private string $title,
        private ?string $description,
        private ?string $category,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {
    }

    public static function create(
        string $id,
        string $title,
        ?string $description = null,
        ?string $category = null
    ): self {
        if (trim($title) === '') {
            throw new InvalidOperationException('Requirement title cannot be empty.');
        }
        $now = new DateTimeImmutable();
        return new self($id, trim($title), $description, $category, $now, $now);
    }

    public function update(?string $title = null, ?string $description = null, ?string $category = null): void
    {
        if ($title !== null && trim($title) === '') {
            throw new InvalidOperationException('Requirement title cannot be empty.');
        }
        $this->title = $title !== null ? trim($title) : $this->title;
        $this->description = $description ?? $this->description;
        $this->category = $category ?? $this->category;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function category(): ?string
    {
        return $this->category;
    }
}