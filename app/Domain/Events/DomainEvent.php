<?php

declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
abstract class DomainEvent
{
    public readonly string $eventId;

    public function __construct(
        public readonly string $eventType,
        public readonly int $eventVersion,
        public readonly string $aggregateId,
        public readonly DateTimeImmutable $timestamp,
        public readonly ?string $correlationId = null
    ) {
        $this->eventId = Uuid::uuid4()->toString();
    }
}
