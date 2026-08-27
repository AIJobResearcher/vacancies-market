<?php
declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

final class VacancyUpdatedEvent extends DomainEvent
{
    public function __construct(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $timestamp,
        ?string $correlationId,
        public readonly array $changes
    ) {
        parent::__construct(
            eventId: $eventId,
            eventType: 'VacancyUpdated',
            eventVersion: 1,
            aggregateId: $aggregateId,
            timestamp: $timestamp,
            correlationId: $correlationId,
        );
    }
}