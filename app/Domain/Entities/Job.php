<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\DomainException;
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
        private ?string $category,
        private ?string $subCategory,
        private ?JobId $parentJobId,
        private ?string $description,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private int $version,
        private ?DateTimeImmutable $deletedAt = null
    ) {}

    public static function create(
        JobId $id,
        string $title,
        ?string $category = null,
        ?string $subCategory = null,
        ?JobId $parentJobId = null,
        ?string $description = null,
        ?string $correlationId = null
    ): self {
        if (trim($title) === '') {
            throw new JobTitleEmptyException;
        }

        $now = new DateTimeImmutable;

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

    public function addRequirement(RequirementId $requirementId): void
    {
        foreach ($this->requirementIds as $existing) {
            if ($existing->equals($requirementId)) {
                throw new RequirementAlreadyAssignedException($requirementId->value());
            }
        }

        $this->requirementIds[] = $requirementId;
        $this->updatedAt = new DateTimeImmutable;
        $this->version++;
    }

    public function removeRequirement(RequirementId $requirementId): void
    {
        foreach ($this->requirementIds as $key => $existing) {
            if ($existing->equals($requirementId)) {
                unset($this->requirementIds[$key]);
                $this->requirementIds = array_values($this->requirementIds);
                $this->updatedAt = new DateTimeImmutable;
                $this->version++;

                return;
            }
        }

        throw new RequirementNotAssignedException($requirementId->value());
    }

    /**
     * Soft delete the Job.
     *
     * @throws DomainException if there are active VacancyJobAssignment references
     *                         (this check must be performed by the application layer before calling)
     */
    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable;
        $this->version++;
    }

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

    public function category(): ?string
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

    public function description(): ?string
    {
        return $this->description;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
