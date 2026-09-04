<?php

declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

final class VacancyUpdatedEvent extends DomainEvent
{
    /**
     * @param array{
     *     id: string,
     *     employer_id: string,
     *     title: string,
     *     description: string|null,
     *     salary: array{min: int, max: int|null, currency: string},
     *     status: string,
     *     country: string|null,
     *     city: string|null,
     *     employment_type: string,
     *     workplace: string,
     *     posted_at: string,
     *     created_at: string,
     *     updated_at: string,
     *     closed_at: string|null,
     *     version: int,
     *     external_urls: string[],
     *     internal_url: string|null,
     *     requirements: string[],
     *     jobs: string[]
     * } $changes
     */
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
