<?php
declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

final class VacancyClosedEvent extends DomainEvent
{
    public function __construct(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $timestamp,
        ?string $correlationId
    ) {
        parent::__construct(
            eventId: $eventId,
            eventType: 'VacancyClosed',
            eventVersion: 1,
            aggregateId: $aggregateId,
            timestamp: $timestamp,
            correlationId: $correlationId,
        );
    }
}