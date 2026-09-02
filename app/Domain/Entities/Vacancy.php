<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\Events\DomainEvent;
use App\Domain\Events\VacancyClosedEvent;
use App\Domain\Events\VacancyImportedEvent;
use App\Domain\Events\VacancyMergedEvent;
use App\Domain\Events\VacancyUpdatedEvent;
use App\Domain\Exceptions\StateConflictException\JobAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\JobNotAssignedException;
use App\Domain\Exceptions\StateConflictException\RequirementAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\RequirementNotAssignedException;
use App\Domain\Exceptions\StateConflictException\VacancyAlreadyClosedException;
use App\Domain\Exceptions\StateConflictException\VacancyAlreadyOpenException;
use App\Domain\Exceptions\ValidationException\VacancyExternalUrlsEmptyException;
use App\Domain\Exceptions\ValidationException\VacancyTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\EntityIds\VacancyJobAssignmentId;
use App\Domain\ValueObjects\EntityIds\VacancyRequirementAssignmentId;
use App\Domain\ValueObjects\ExternalUrls;
use App\Domain\ValueObjects\Salary;
use DateTimeImmutable;

final class Vacancy
{
    /** @var DomainEvent[] */
    private array $events = [];

    /** @var VacancyRequirementAssignment[] */
    private array $requirementAssignments = [];

    /** @var VacancyJobAssignment[] */
    private array $jobAssignments = [];

    /** @var VacancySource[] */
    private array $sources = [];

    private function __construct(
        private readonly VacancyId $id,
        private readonly EmployerId $employerId,
        private string $title,
        private ?string $description,
        private Salary $salary,
        private VacancyStatusEnum $status,
        private ?string $country,
        private ?string $city,
        private EmploymentTypeEnum $employmentType,
        private WorkplaceEnum $workplace,
        private DateTimeImmutable $postedAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $closedAt,
        private int $version,
        private ExternalUrls $externalUrls,
        private ?string $internalUrl = null
    ) {
    }

    public static function create(
        VacancyId $id,
        EmployerId $employerId,
        string $title,
        string $description,
        Salary $salary,
        ?string $country,
        ?string $city,
        EmploymentTypeEnum $employmentType,
        WorkplaceEnum $workplace,
        DateTimeImmutable $postedAt,
        ExternalUrls $externalUrls,
        ?string $internalUrl = null,
        ?string $correlationId = null
    ): self {
        if (trim($title) === '') {
            throw new VacancyTitleEmptyException;
        }

        if ($externalUrls->isEmpty()) {
            throw new VacancyExternalUrlsEmptyException;
        }

        $now = new DateTimeImmutable;
        $vacancy = new self(
            $id,
            $employerId,
            trim($title),
            $description,
            $salary,
            VacancyStatusEnum::OPEN,
            $country,
            $city,
            $employmentType,
            $workplace,
            $postedAt,
            $now,
            $now,
            null,
            1,
            $externalUrls,
            $internalUrl
        );
        $vacancy->recordEvent(
            new VacancyImportedEvent(
                $id->value(),
                $id->value(),
                $now,
                $correlationId,
                $vacancy->toArray()
            )
        );
        return $vacancy;
    }

    public function updateDetails(
        ?string $title = null,
        ?string $description = null,
        ?Salary $salary = null,
        ?string $country = null,
        ?string $city = null,
        ?EmploymentTypeEnum $employmentType = null,
        ?WorkplaceEnum $workplace = null,
        ?DateTimeImmutable $postedAt = null,
        ?ExternalUrls $externalUrls = null,
        ?string $internalUrl = null
    ): void {
        if ($title !== null && trim($title) === '') {
            throw new VacancyTitleEmptyException;
        }

        if ($externalUrls !== null && $externalUrls->isEmpty()) {
            throw new VacancyExternalUrlsEmptyException;
        }

        $this->title = $title !== null ? trim($title) : $this->title;
        $this->description = $description ?? $this->description;
        $this->salary = $salary ?? $this->salary;
        $this->country = $country ?? $this->country;
        $this->city = $city ?? $this->city;
        $this->employmentType = $employmentType ?? $this->employmentType;
        $this->workplace = $workplace ?? $this->workplace;
        $this->postedAt = $postedAt ?? $this->postedAt;
        $this->externalUrls = $externalUrls ?? $this->externalUrls;
        $this->internalUrl = $internalUrl ?? $this->internalUrl;

        $this->updatedAt = new DateTimeImmutable;
        $this->version++;
        $this->recordEvent(
            new VacancyUpdatedEvent(
                $this->id->value(),
                $this->id->value(),
                $this->updatedAt,
                null, // correlationId can be passed if needed
                $this->toArray()
            )
        );
    }

    public function close(): void
    {
        if ($this->status === VacancyStatusEnum::CLOSED) {
            throw new VacancyAlreadyClosedException($this->id->value());
        }
        $this->status = VacancyStatusEnum::CLOSED;
        $this->closedAt = new DateTimeImmutable;
        $this->updatedAt = $this->closedAt;
        $this->version++;
        $this->recordEvent(
            new VacancyClosedEvent(
                $this->id->value(),
                $this->id->value(),
                $this->closedAt,
                null
            )
        );
    }

    public function reopen(): void
    {
        if ($this->status === VacancyStatusEnum::OPEN) {
            throw new VacancyAlreadyOpenException($this->id->value());
        }
        $this->status = VacancyStatusEnum::OPEN;
        $this->closedAt = null;
        $this->updatedAt = new DateTimeImmutable;
        $this->version++;
        $this->recordEvent(
            new VacancyUpdatedEvent(
                $this->id->value(),
                $this->id->value(),
                $this->updatedAt,
                null,
                $this->toArray()
            )
        );
    }

    public function mergeFrom(Vacancy $other, array $mergedIds): void
    {
        // Take canonical fields from $other (the source of truth after merge)
        $this->title = $other->title;
        $this->description = $other->description;
        $this->salary = $other->salary;
        $this->country = $other->country;
        $this->city = $other->city;
        $this->employmentType = $other->employmentType;
        $this->workplace = $other->workplace;
        $this->externalUrls = $other->externalUrls;
        $this->internalUrl = $other->internalUrl;
        $this->updatedAt = new DateTimeImmutable;
        $this->version++;
        $this->recordEvent(
            new VacancyMergedEvent(
                $this->id->value(),
                $this->id->value(),
                $this->updatedAt,
                null,
                $mergedIds
            )
        );
    }

    public function addRequirement(RequirementId $requirementId): void
    {
        foreach ($this->requirementAssignments as $assignment) {
            if ($assignment->getRequirementId()->equals($requirementId)) {
                throw new RequirementAlreadyAssignedException($requirementId->value());
            }
        }
        $assignment = new VacancyRequirementAssignment(
            VacancyRequirementAssignmentId::generate(),
            $this->id,
            $requirementId,
            new DateTimeImmutable,
        );
        $this->requirementAssignments[] = $assignment;
        $this->updatedAt = new DateTimeImmutable;
        $this->version++;
    }

    public function removeRequirement(RequirementId $requirementId): void
    {
        foreach ($this->requirementAssignments as $key => $assignment) {
            if ($assignment->getRequirementId()->equals($requirementId)) {
                unset($this->requirementAssignments[$key]);
                $this->requirementAssignments = array_values($this->requirementAssignments);
                $this->updatedAt = new DateTimeImmutable;
                $this->version++;
                return;
            }
        }

        throw new RequirementNotAssignedException($requirementId->value());
    }

    public function assignToJob(JobId $jobId, ?int $relevanceScore = null): void
    {
        foreach ($this->jobAssignments as $assignment) {
            if ($assignment->jobId()->equals($jobId) && $assignment->isActive()) {
                throw new JobAlreadyAssignedException($jobId->value());
            }
        }
        $assignment = new VacancyJobAssignment(
            VacancyJobAssignmentId::generate(),
            $this->id,
            $jobId,
            new DateTimeImmutable,
            $relevanceScore
        );
        $this->jobAssignments[] = $assignment;
        $this->updatedAt = new DateTimeImmutable;
        $this->version++;
    }

    public function unassignFromJob(JobId $jobId): void
    {
        foreach ($this->jobAssignments as $assignment) {
            if ($assignment->jobId()->equals($jobId) && $assignment->isActive()) {
                $assignment->deactivate();
                $this->updatedAt = new DateTimeImmutable;
                $this->version++;
                return;
            }
        }
        throw new JobNotAssignedException($jobId->value());
    }

    public function addSource(VacancySource $source): void
    {
        foreach ($this->sources as $existing) {
            if ($existing->sourceKey() === $source->sourceKey()
                && $existing->externalVacancyId() === $source->externalVacancyId()) {
                $existing->updateLastSeenAt(new DateTimeImmutable);
                $this->version++;
                return;
            }
        }
        $this->sources[] = $source;
        $this->updatedAt = new DateTimeImmutable;
        $this->version++;
    }

    public function id(): VacancyId
    {
        return $this->id;
    }

    public function employerId(): EmployerId
    {
        return $this->employerId;
    }

    public function status(): string
    {
        return $this->status->value;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'employer_id' => $this->employerId->value(),
            'title' => $this->title,
            'description' => $this->description,
            'salary' => [
                'min' => $this->salary->min(),
                'max' => $this->salary->max(),
                'currency' => $this->salary->currency(),
            ],
            'status' => $this->status->value,
            'country' => $this->country,
            'city' => $this->city,
            'employment_type' => $this->employmentType->value,
            'workplace' => $this->workplace->value,
            'posted_at' => $this->postedAt->format(DATE_ATOM),
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'updated_at' => $this->updatedAt->format(DATE_ATOM),
            'closed_at' => $this->closedAt?->format(DATE_ATOM),
            'version' => $this->version,
            'external_urls' => $this->externalUrls->toArray(),
            'internal_url' => $this->internalUrl,
            'requirements' => array_map(fn($a) => $a->getRequirementId()->value(), $this->requirementAssignments),
            'jobs' => array_map(fn($a) => $a->jobId()->value(), $this->jobAssignments),
        ];
    }

    /** @return DomainEvent[] */
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->events[] = $event;
    }
}