<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\StateConflictException\ContentNotAssignedException;
use App\Domain\Exceptions\ValidationException\ExternalUrlInvalidException;
use App\Domain\ValueObjects\EntityIds\ContentId;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Domain\ValueObjects\EntityIds\SourceId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use DateTimeImmutable;

final class Source
{
    /**
     * @param list<Content> $contents
     */
    private function __construct(
        private readonly SourceId $id,
        private readonly VacancyId $vacancyId,
        private PortalId $portalId,
        private ?string $externalVacancyId,
        private string $externalUrl,
        private string $title,
        private DateTimeImmutable $postedAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private array $contents = [],
    ) {
    }

    /**
     * @param list<Content> $contents
     */
    public static function createSource(
        SourceId $id,
        VacancyId $vacancyId,
        PortalId $portalId,
        ?string $externalVacancyId,
        string $externalUrl,
        string $title,
        DateTimeImmutable $postedAt,
        array $contents = [],
    ): self {
        if (trim($externalUrl) === '') {
            throw new ExternalUrlInvalidException($externalUrl);
        }

        $now = new DateTimeImmutable();

        return new self(
            $id,
            $vacancyId,
            $portalId,
            $externalVacancyId,
            $externalUrl,
            $title,
            $postedAt,
            $now,
            $now,
            $contents,
        );
    }

    /**
     * Restores a Source from persisted state without validation or events.
     *
     * @param list<Content> $contents
     */
    public static function reconstitute(
        SourceId $id,
        VacancyId $vacancyId,
        PortalId $portalId,
        ?string $externalVacancyId,
        string $externalUrl,
        string $title,
        DateTimeImmutable $postedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        array $contents = [],
    ): self {
        return new self(
            $id,
            $vacancyId,
            $portalId,
            $externalVacancyId,
            $externalUrl,
            $title,
            $postedAt,
            $createdAt,
            $updatedAt,
            $contents,
        );
    }

    public function addContent(Content $content): void
    {
        foreach ($this->contents as $existing) {
            if ($existing->id()->equals($content->id())) {
                throw new ContentNotAssignedException($content->id()->value());
            }
        }

        $this->contents[] = $content;
        $this->touch();
    }

    public function updateContent(Content $content): void
    {
        foreach ($this->contents as $key => $existing) {
            if ($existing->id()->equals($content->id())) {
                $this->contents[$key] = $content;
                $this->touch();

                return;
            }
        }

        throw new ContentNotAssignedException($content->id()->value());
    }

    public function removeContent(ContentId $contentId): void
    {
        $remaining = [];
        $removed = false;

        foreach ($this->contents as $existing) {
            if ($existing->id()->equals($contentId)) {
                $removed = true;

                continue;
            }

            $remaining[] = $existing;
        }

        if (! $removed) {
            throw new ContentNotAssignedException($contentId->value());
        }

        $this->contents = $remaining;
        $this->touch();
    }

    public function refresh(PortalId $portalId, string $title, DateTimeImmutable $postedAt): void
    {
        $this->portalId = $portalId;
        $this->title = $title;
        $this->postedAt = $postedAt;
        $this->touch();
    }

    public function id(): SourceId
    {
        return $this->id;
    }

    public function vacancyId(): VacancyId
    {
        return $this->vacancyId;
    }

    public function portalId(): PortalId
    {
        return $this->portalId;
    }

    public function externalVacancyId(): ?string
    {
        return $this->externalVacancyId;
    }

    public function externalUrl(): string
    {
        return $this->externalUrl;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function postedAt(): DateTimeImmutable
    {
        return $this->postedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return list<Content> */
    public function contents(): array
    {
        return $this->contents;
    }

    /**
     * @return array{
     *     id: string,
     *     portal_id: string,
     *     external_vacancy_id: string|null,
     *     external_url: string,
     *     title: string,
     *     posted_at: string,
     *     contents: list<array{id: string, type: string, value: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'portal_id' => $this->portalId->value(),
            'external_vacancy_id' => $this->externalVacancyId,
            'external_url' => $this->externalUrl,
            'title' => $this->title,
            'posted_at' => $this->postedAt->format(DATE_ATOM),
            'contents' => array_map(
                static fn (Content $content): array => $content->toArray(),
                $this->contents,
            ),
        ];
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
