<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\OwnershipException\VacancyBelongsToDifferentEmployerException;
use App\Domain\Exceptions\StateConflictException\VacancyNotClosedException;
use App\Domain\Exceptions\ValidationException\EmployerTitleEmptyException;
use App\Domain\ValueObjects\EmployerContacts;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use DateTimeImmutable;

final class Employer
{
    /**
     * @param list<int> $locationIds
     */
    private function __construct(
        private readonly EmployerId $id,
        private string $title,
        private ?string $description,
        private ?EmployerContacts $contacts,
        private ?string $logoUrl,
        private array $locationIds,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private int $version
    ) {
    }

    /**
     * @param list<int> $locationIds
     */
    public static function create(
        EmployerId $id,
        string $title,
        ?string $description = null,
        ?EmployerContacts $contacts = null,
        ?string $logoUrl = null,
        array $locationIds = [],
    ): self {
        if (trim($title) === '') {
            throw new EmployerTitleEmptyException();
        }
        $now = new DateTimeImmutable();

        return new self(
            $id,
            trim($title),
            $description,
            $contacts,
            $logoUrl,
            $locationIds,
            $now,
            $now,
            1
        );
    }

    /**
     * Restores an Employer from persisted state without validation.
     *
     * @param list<int> $locationIds
     */
    public static function reconstitute(
        EmployerId $id,
        string $title,
        ?string $description,
        ?EmployerContacts $contacts,
        ?string $logoUrl,
        array $locationIds,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        return new self(
            $id,
            $title,
            $description,
            $contacts,
            $logoUrl,
            $locationIds,
            $createdAt,
            $updatedAt,
            $version,
        );
    }

    /**
     * @param list<int>|null $locationIds
     */
    public function updateDetails(
        ?string $title = null,
        ?string $description = null,
        ?EmployerContacts $contacts = null,
        ?string $logoUrl = null,
        ?array $locationIds = null
    ): void {
        if ($title !== null && trim($title) === '') {
            throw new EmployerTitleEmptyException();
        }

        $this->title = $title !== null ? trim($title) : $this->title;
        $this->description = $description ?? $this->description;
        $this->contacts = $contacts ?? $this->contacts;
        $this->logoUrl = $logoUrl ?? $this->logoUrl;
        $this->locationIds = $locationIds ?? $this->locationIds;
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function addVacancy(Vacancy $vacancy): void
    {
        if (! $vacancy->employerId()->equals($this->id)) {
            throw new VacancyBelongsToDifferentEmployerException($vacancy->id()->value(), $this->id->value());
        }
    }

    public function removeVacancy(Vacancy $vacancy): void
    {
        if ($vacancy->status() !== 'closed') {
            throw new VacancyNotClosedException($vacancy->id()->value());
        }
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     * @psalm-suppress UnusedParam
     */
    public function addInterviewer(Interviewer $interviewer): void
    {
        // The Interviewer is a child entity of this aggregate; no collection is held here.
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     * @psalm-suppress UnusedParam
     */
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

    public function contacts(): ?EmployerContacts
    {
        return $this->contacts;
    }

    public function logoUrl(): ?string
    {
        return $this->logoUrl;
    }

    /** @return list<int> */
    public function locationIds(): array
    {
        return $this->locationIds;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     description: string|null,
     *     contacts: list<array{type: string, value: string}>|null,
     *     logo_url: string|null,
     *     location_ids: list<int>,
     *     created_at: string,
     *     updated_at: string,
     *     version: int
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'title' => $this->title,
            'description' => $this->description,
            'contacts' => $this->contacts?->toArray(),
            'logo_url' => $this->logoUrl,
            'location_ids' => $this->locationIds,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'updated_at' => $this->updatedAt->format(DATE_ATOM),
            'version' => $this->version,
        ];
    }
}
