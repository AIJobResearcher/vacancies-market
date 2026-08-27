<?php

declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

final class VacancyMergedEvent extends DomainEvent
{
    public function __construct(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $timestamp,
        ?string $correlationId,
        public readonly array $mergedVacancyIds
    ) {
        parent::__construct(
            eventId: $eventId,
            eventType: 'VacancyMerged',
            eventVersion: 1,
            aggregateId: $aggregateId,
            timestamp: $timestamp,
            correlationId: $correlationId,
        );
    }
}