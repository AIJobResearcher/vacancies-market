<?php

declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

abstract class DomainEvent
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly int $eventVersion,
        public readonly string $aggregateId,
        public readonly DateTimeImmutable $timestamp,
        public readonly ?string $correlationId = null
    ) {}
}