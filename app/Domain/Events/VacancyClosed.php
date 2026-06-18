<?php
declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

/**
 * Domain event: VacancyClosed
 *
 * Emitted when a vacancy is closed on the source portal. Closed vacancies
 * cannot be manually reopened; if a portal later shows it reopened an import
 * will create a new version and emit VacancyImported/VacancyUpdated as needed.
 */
final class VacancyClosed
{
    public string $vacancyId;
    public DateTimeImmutable $occurredAt;

    public function __construct(string $vacancyId, ?DateTimeImmutable $occurredAt = null)
    {
        $this->vacancyId = $vacancyId;
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
    }
}
