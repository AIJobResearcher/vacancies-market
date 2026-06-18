<?php
declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Contacts;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Employer aggregate root
 *
 * Bounded Context: Vacancy Management (Vacancies Service)
 *
 * Responsibilities:
 * - Manage employer data and relations to vacancies and interviewers.
 *
 * Business invariants (see docs/domain-model and docs/adr/adr-013-idempotency.md):
 * - An interviewer always belongs to exactly one employer.
 * - A vacancy cannot exist without an employer.
 *
 * Implementation notes:
 * - This is a pure domain aggregate (POPO). Persistence is implemented via
 *   repository interfaces in `app/Domain/Repositories` and infrastructure in
 *   `app/Infrastructure/Persistence`.
 *
 * See also: docs/architecture-overview.md, docs/adr/adr-011-outbox-pattern.md,
 * docs/adr/adr-012-event-versioning.md
 */
final class Employer
{
    public readonly string $id;
    public string $name;
    public ?string $description;
    public ?string $website;
    public Contacts $contacts;
    public ?string $portalId;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;

    /** @var string[] List of vacancy ids belonging to this employer */
    private array $vacancyIds = [];

    /** @var string[] List of interviewer ids */
    private array $interviewerIds = [];

    public function __construct(
        string $id,
        string $name,
        ?string $description,
        ?string $website,
        Contacts $contacts,
        ?string $portalId = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('Employer name must not be empty');
        }

        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->website = $website;
        $this->contacts = $contacts;
        $this->portalId = $portalId;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? $this->createdAt;
    }

    /**
     * Add vacancy id to employer
     */
    public function addVacancy(string $vacancyId): void
    {
        if (!in_array($vacancyId, $this->vacancyIds, true)) {
            $this->vacancyIds[] = $vacancyId;
            $this->touch();
        }
    }

    public function removeVacancy(string $vacancyId): void
    {
        $this->vacancyIds = array_values(array_filter($this->vacancyIds, fn($id) => $id !== $vacancyId));
        $this->touch();
    }

    public function addInterviewer(string $interviewerId): void
    {
        if (!in_array($interviewerId, $this->interviewerIds, true)) {
            $this->interviewerIds[] = $interviewerId;
            $this->touch();
        }
    }

    public function removeInterviewer(string $interviewerId): void
    {
        $this->interviewerIds = array_values(array_filter($this->interviewerIds, fn($id) => $id !== $interviewerId));
        $this->touch();
    }

    /** @return string[] */
    public function vacancyIds(): array
    {
        return $this->vacancyIds;
    }

    /** @return string[] */
    public function interviewerIds(): array
    {
        return $this->interviewerIds;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
