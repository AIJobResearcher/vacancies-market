<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\InvalidOperationException;
use DateTimeImmutable;

final class Job
{
    /** @var string[] */
    private array $requirementIds = [];

    private function __construct(
        private string $id,
        private string $title,
        private ?string $category,
        private ?string $subCategory,
        private ?string $parentJobId,
        private ?string $description,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private int $version,
        private ?DateTimeImmutable $deletedAt = null
    ) {
    }

    public static function create(
        string $id,
        string $title,
        ?string $category = null,
        ?string $subCategory = null,
        ?string $parentJobId = null,
        ?string $description = null,
        ?string $correlationId = null
    ): self {
        if (trim($title) === '') {
            throw new InvalidOperationException('Job title cannot be empty.');
        }
        $now = new DateTimeImmutable();
        return new self(
            $id,
            trim($title),
            $category,
            $subCategory,
            $parentJobId,
            $description,
            $now,
            $now,
            1
        );
    }

    public function addRequirement(string $requirementId): void
    {
        if (in_array($requirementId, $this->requirementIds, true)) {
            throw new InvalidOperationException('Requirement already assigned to this job.');
        }
        $this->requirementIds[] = $requirementId;
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function removeRequirement(string $requirementId): void
    {
        $index = array_search($requirementId, $this->requirementIds, true);
        if ($index === false) {
            throw new InvalidOperationException('Requirement not assigned to this job.');
        }
        unset($this->requirementIds[$index]);
        $this->requirementIds = array_values($this->requirementIds);
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function softDelete(): void
    {
        // Application layer must check that no active Vacancy references this Job.
        $this->deletedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function version(): int
    {
        return $this->version;
    }
}