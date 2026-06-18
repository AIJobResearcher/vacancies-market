<?php
declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

/**
 * Domain event: ExternalPortalUnreachable
 *
 * Emitted by importers when a configured portal cannot be reached. This event
 * is important for monitoring/alerting and retry logic in import pipelines.
 */
final class ExternalPortalUnreachable
{
    public string $portalId;
    public string $reason;
    public DateTimeImmutable $occurredAt;

    public function __construct(string $portalId, string $reason, ?DateTimeImmutable $occurredAt = null)
    {
        $this->portalId = $portalId;
        $this->reason = $reason;
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
    }
}
