<?php
declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

/**
 * Domain event: InterviewerAssigned
 *
 * Emitted when an interviewer is linked to a vacancy. Consumers (CRM, search)
 * should update respective models/indices.
 */
final class InterviewerAssigned
{
    public string $interviewerId;
    public string $vacancyId;
    public DateTimeImmutable $occurredAt;

    public function __construct(string $interviewerId, string $vacancyId, ?DateTimeImmutable $occurredAt = null)
    {
        $this->interviewerId = $interviewerId;
        $this->vacancyId = $vacancyId;
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
    }
}
