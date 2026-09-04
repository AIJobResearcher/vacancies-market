<?php

declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

/** @psalm-suppress PossiblyUnusedProperty */
final class EmployerImportedEvent extends DomainEvent
{
    /**
     * @param array{
     *     id: string,
     *     title: string,
     *     description: string|null,
     *     website: string|null,
     *     email: string|null,
     *     phone: string|null,
     *     logo_url: string|null,
     *     is_active: bool,
     *     created_at: string,
     *     updated_at: string,
     *     version: int
     * } $employerData
     */
    public function __construct(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $timestamp,
        ?string $correlationId,
        public readonly array $employerData
    ) {
        parent::__construct(
            eventId: $eventId,
            eventType: 'EmployerImported',
            eventVersion: 1,
            aggregateId: $aggregateId,
            timestamp: $timestamp,
            correlationId: $correlationId,
        );
    }
}
