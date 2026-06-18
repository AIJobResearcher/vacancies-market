<?php
declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Salary;
use App\Domain\Enums\VacancyStatus;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Vacancy domain entity / aggregate (also used as search root)
 *
 * Bounded Context: Vacancy Management (Vacancies Service)
 *
 * Notes & invariants:
 * - Vacancies are created/updated only by import processes from external portals
 *   (see docs/architecture-overview.md and docs/adr/adr-011-outbox-pattern.md).
 * - An imported vacancy is considered valid only if it contains a title,
 *   an employer and a publication date.
 * - Updates from portals create a new version of the vacancy and preserve
 *   change history. See docs/adr/adr-012-event-versioning.md for event versioning.
 *
 * Behavioural methods mutate domain state and bump `version` when appropriate.
 */
final class Vacancy
{
    public readonly string $id;
    public readonly string $employerId;
    public string $title;
    public string $description;
    /** @var string[] */
    public array $requirements = [];
    public ?Salary $salary;
    public VacancyStatus $status;
    public ?string $country;
    public ?string $city;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;
    public int $version = 1;

    public function __construct(
        string $id,
        string $employerId,
        string $title,
        string $description,
        array $requirements,
        ?Salary $salary,
        VacancyStatus $status,
        ?string $country,
        ?string $city,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        int $version = 1
    ) {
        if ($title === '') {
            throw new InvalidArgumentException('Vacancy must have a title');
        }
        if ($employerId === '') {
            throw new InvalidArgumentException('Vacancy must have an employer id');
        }

        $this->id = $id;
        $this->employerId = $employerId;
        $this->title = $title;
        $this->description = $description;
        $this->requirements = $requirements;
        $this->salary = $salary;
        $this->status = $status;
        $this->country = $country;
        $this->city = $city;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? $this->createdAt;
        $this->version = $version;
    }

    public function close(): void
    {
        if ($this->status === VacancyStatus::CLOSED) {
            return;
        }
        $this->status = VacancyStatus::CLOSED;
        $this->newVersion();
    }

    public function updateDescription(string $description): void
    {
        if ($this->description === $description) {
            return;
        }
        $this->description = $description;
        $this->newVersion();
    }

    public function updateRequirements(array $requirements): void
    {
        if ($this->requirements === $requirements) {
            return;
        }
        $this->requirements = $requirements;
        $this->newVersion();
    }

    public function reopen(DateTimeImmutable $publishedAt): void
    {
        // Reopening increments version and sets status to open
        $this->status = VacancyStatus::OPEN;
        $this->createdAt = $publishedAt;
        $this->newVersion();
    }

    private function newVersion(): void
    {
        $this->version++;
        $this->updatedAt = new DateTimeImmutable();
    }
}
