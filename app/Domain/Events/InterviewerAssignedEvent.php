<?php

declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

/** @psalm-suppress UnusedClass */
final class InterviewerAssignedEvent extends DomainEvent
{
    public function __construct(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $timestamp,
        ?string $correlationId,
        public readonly string $vacancyId,
        public readonly string $interviewerId
    ) {
        parent::__construct(
            eventId: $eventId,
            eventType: 'InterviewerAssigned',
            eventVersion: 1,
            aggregateId: $aggregateId,
            timestamp: $timestamp,
            correlationId: $correlationId,
        );
    }
}
