<?php
declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

/**
 * Domain event: VacancyImported
 *
 * Produced after a successful import of a new vacancy from an external portal.
 * Event schema is defined in docs/asyncapi/events.yaml (source of truth for events).
 */
final class VacancyImported
{
    public string $vacancyId;
    public string $employerId;
    public DateTimeImmutable $occurredAt;

    public function __construct(string $vacancyId, string $employerId, ?DateTimeImmutable $occurredAt = null)
    {
        $this->vacancyId = $vacancyId;
        $this->employerId = $employerId;
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
    }
}
