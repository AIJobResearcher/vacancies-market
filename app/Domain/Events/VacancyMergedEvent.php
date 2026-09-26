<?php

declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

final class VacancyMergedEvent extends DomainEvent
{
    /** @param string[] $mergedVacancyIds */
    public function __construct(
        string $aggregateId,
        DateTimeImmutable $timestamp,
        ?string $correlationId,
        public readonly array $mergedVacancyIds
    ) {
        parent::__construct(
            eventType: 'VacancyMerged',
            eventVersion: 1,
            aggregateId: $aggregateId,
            timestamp: $timestamp,
            correlationId: $correlationId,
        );
    }
}
