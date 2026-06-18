<?php
declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

/**
 * Domain event: VacancyUpdated
 *
 * Emitted when an existing vacancy changes (text, requirements, salary, etc.).
 * Consumers should use `changes` to update index or downstream models.
 * See docs/asyncapi/events.yaml and docs/adr/adr-012-event-versioning.md.
 */
final class VacancyUpdated
{
    public string $vacancyId;
    public array $changes;
    public DateTimeImmutable $occurredAt;

    public function __construct(string $vacancyId, array $changes, ?DateTimeImmutable $occurredAt = null)
    {
        $this->vacancyId = $vacancyId;
        $this->changes = $changes;
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
    }
}
