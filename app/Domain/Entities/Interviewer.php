<?php
declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Contacts;
use DateTimeImmutable;
use InvalidArgumentException;

final class Interviewer
{
    /**
     * Interviewer entity
     *
     * Business invariant: an interviewer always belongs to exactly one employer.
     * Behaviour: assignToVacancy/unassignFromVacancy manage links to vacancies
     * and update timestamps. Persistence is handled via repositories.
     *
     * See: docs/architecture-overview.md, docs/glossary.md
     */
    public readonly string $id;
    public readonly string $employerId;
    public string $fullName;
    public ?string $position;
    public ?string $email;
    public ?string $portalId;
    public ?string $profileUrl;
    /** @var string[] */
    private array $vacancyIds = [];
    public Contacts $contacts;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $employerId,
        string $fullName,
        ?string $position,
        ?string $email,
        Contacts $contacts,
        ?string $portalId = null,
        ?string $profileUrl = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        if ($fullName === '') {
            throw new InvalidArgumentException('Interviewer full name must not be empty');
        }
        if ($employerId === '') {
            throw new InvalidArgumentException('Interviewer must belong to an employer');
        }

        $this->id = $id;
        $this->employerId = $employerId;
        $this->fullName = $fullName;
        $this->position = $position;
        $this->email = $email;
        $this->contacts = $contacts;
        $this->portalId = $portalId;
        $this->profileUrl = $profileUrl;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? $this->createdAt;
    }

    public function assignToVacancy(string $vacancyId): void
    {
        if (!in_array($vacancyId, $this->vacancyIds, true)) {
            $this->vacancyIds[] = $vacancyId;
            $this->touch();
        }
    }

    public function unassignFromVacancy(string $vacancyId): void
    {
        $this->vacancyIds = array_values(array_filter($this->vacancyIds, fn($id) => $id !== $vacancyId));
        $this->touch();
    }

    /** @return string[] */
    public function vacancyIds(): array
    {
        return $this->vacancyIds;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
