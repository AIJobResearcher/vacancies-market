<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\StateConflictException\RequirementAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\RequirementNotAssignedException;
use App\Domain\Exceptions\ValidationException\JobTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use DateTimeImmutable;

final class Job
{
    /** @var RequirementId[] */
    private array $requirementIds = [];

    private function __construct(
        private readonly JobId $id,
        private string $title,
        private string $category,
        private ?string $subCategory,
        private ?JobId $parentJobId,
        private ?string $description,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private int $version,
        private ?DateTimeImmutable $deletedAt = null
    ) {
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     * @psalm-suppress UnusedParam
     */
    public static function createJob(
        JobId $id,
        string $title,
        string $category,
        ?string $subCategory = null,
        ?JobId $parentJobId = null,
        ?string $description = null,
        ?string $correlationId = null
    ): self {
        if (trim($title) === '') {
            throw new JobTitleEmptyException();
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

    /**
     * Restores a Job from persisted state without validation or events.
     *
     * @param RequirementId[] $requirementIds
     */
    public static function reconstitute(
        JobId $id,
        string $title,
        string $category,
        ?string $subCategory,
        ?JobId $parentJobId,
        ?string $description,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
        ?DateTimeImmutable $deletedAt = null,
        array $requirementIds = [],
    ): self {
        $job = new self(
            $id,
            $title,
            $category,
            $subCategory,
            $parentJobId,
            $description,
            $createdAt,
            $updatedAt,
            $version,
            $deletedAt,
        );
        $job->requirementIds = $requirementIds;

        return $job;
    }

    public function updateJob(
        ?string $title = null,
        ?string $category = null,
        ?string $subCategory = null,
        ?string $description = null,
        ?JobId $parentJobId = null
    ): void {
        if ($title !== null && trim($title) === '') {
            throw new JobTitleEmptyException();
        }

        $this->title = $title !== null ? trim($title) : $this->title;
        $this->category = $category ?? $this->category;
        $this->subCategory = $subCategory ?? $this->subCategory;
        $this->description = $description ?? $this->description;
        $this->parentJobId = $parentJobId ?? $this->parentJobId;
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function assignRequirement(RequirementId $requirementId): void
    {
        foreach ($this->requirementIds as $existing) {
            if ($existing->equals($requirementId)) {
                throw new RequirementAlreadyAssignedException($requirementId->value());
            }
        }

        $this->requirementIds[] = $requirementId;
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function unassignRequirement(RequirementId $requirementId): void
    {
        $remaining = [];
        $removed = false;

        foreach ($this->requirementIds as $existing) {
            if ($existing->equals($requirementId)) {
                $removed = true;

                continue;
            }

            $remaining[] = $existing;
        }

        if (! $removed) {
            throw new RequirementNotAssignedException($requirementId->value());
        }

        $this->requirementIds = $remaining;
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    /**
     * Soft deletes the Job: sets `deleted_at` and creates a new version.
     *
     * Active Vacancy assignments must be validated by the application layer before calling.
     */
    public function deleteJob(): void
    {
        $this->deletedAt = new DateTimeImmutable();
        $this->version++;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function id(): JobId
    {
        return $this->id;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function subCategory(): ?string
    {
        return $this->subCategory;
    }

    public function parentJobId(): ?JobId
    {
        return $this->parentJobId;
    }

    /** @return RequirementId[] */
    public function requirementIds(): array
    {
        return $this->requirementIds;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function description(): ?string
    {
        return $this->description;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
