<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Events\DomainEvent;
use App\Domain\Events\EmployerImportedEvent;
use App\Domain\Exceptions\OwnershipException\InterviewerBelongsToDifferentEmployerException;
use App\Domain\Exceptions\OwnershipException\VacancyBelongsToDifferentEmployerException;
use App\Domain\Exceptions\StateConflictException\EmployerInactiveException;
use App\Domain\Exceptions\StateConflictException\VacancyNotClosedException;
use App\Domain\Exceptions\ValidationException\EmployerTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use DateTimeImmutable;

final class Employer
{
    /** @var DomainEvent[] */
    private array $events = [];

    private function __construct(
        private readonly EmployerId $id,
        private string $title,
        private ?string $description,
        private ?string $website,
        private ?string $email,
        private ?string $phone,
        private ?string $logoUrl,
        private bool $isActive,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private int $version
    ) {}

    public static function create(
        EmployerId $id,
        string $title,
        ?string $description = null,
        ?string $website = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $logoUrl = null,
        ?string $correlationId = null
    ): self {
        if (trim($title) === '') {
            throw new EmployerTitleEmptyException;
        }
        $now = new DateTimeImmutable;
        $employer = new self(
            $id,
            trim($title),
            $description,
            $website,
            $email,
            $phone,
            $logoUrl,
            true,
            $now,
            $now,
            1
        );
        $employer->recordEvent(new EmployerImportedEvent(
            $id->value(),
            $id->value(),
            $now,
            $correlationId,
            $employer->toArray()
        ));

        return $employer;
    }

    public function updateDetails(
        ?string $title = null,
        ?string $description = null,
        ?string $website = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $logoUrl = null
    ): void {
        if ($title !== null && trim($title) === '') {
            throw new EmployerTitleEmptyException;
        }

        $this->title = $title !== null ? trim($title) : $this->title;
        $this->description = $description ?? $this->description;
        $this->website = $website ?? $this->website;
        $this->email = $email ?? $this->email;
        $this->phone = $phone ?? $this->phone;
        $this->logoUrl = $logoUrl ?? $this->logoUrl;
        $this->updatedAt = new DateTimeImmutable;
        $this->version++;
    }

    public function addVacancy(Vacancy $vacancy): void
    {
        if (! $this->isActive) {
            throw new EmployerInactiveException($this->id->value());
        }
        if (! $vacancy->employerId()->equals($this->id)) {
            throw new VacancyBelongsToDifferentEmployerException($vacancy->id()->value(), $this->id->value());
        }
        // In real implementation, the vacancy is saved separately; no collection stored here.
    }

    public function removeVacancy(Vacancy $vacancy): void
    {
        if ($vacancy->status() !== 'closed') {
            throw new VacancyNotClosedException($vacancy->id()->value());
        }
    }

    public function addInterviewer(Interviewer $interviewer): void
    {
        if (! $interviewer->employerId()->equals($this->id)) {
            throw new InterviewerBelongsToDifferentEmployerException($interviewer->id()->value(), $this->id->value());
        }
    }

    public function removeInterviewer(Interviewer $interviewer): void
    {
        // Soft delete handled by interviewer itself.
    }

    public function id(): EmployerId
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

    public function website(): ?string
    {
        return $this->website;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function logoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'title' => $this->title,
            'description' => $this->description,
            'website' => $this->website,
            'email' => $this->email,
            'phone' => $this->phone,
            'logo_url' => $this->logoUrl,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'updated_at' => $this->updatedAt->format(DATE_ATOM),
            'version' => $this->version,
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
