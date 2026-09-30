<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Content;
use App\Domain\Entities\Source;
use App\Domain\Enums\ContentTypeEnum;
use App\Domain\Exceptions\StateConflictException\ContentNotAssignedException;
use App\Domain\Exceptions\ValidationException\ExternalUrlInvalidException;
use App\Domain\ValueObjects\EntityIds\ContentId;
use App\Domain\ValueObjects\EntityIds\PortalId;
use App\Domain\ValueObjects\EntityIds\SourceId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SourceTest extends TestCase
{
    public function testReconstituteAndGetters(): void
    {
        $id = SourceId::generate();
        $vacancyId = VacancyId::generate();
        $portalId = PortalId::generate();
        $postedAt = new DateTimeImmutable('2025-01-01T00:00:00+00:00');
        $createdAt = new DateTimeImmutable('2025-01-02T00:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2025-01-03T00:00:00+00:00');

        $source = Source::reconstitute(
            $id,
            $vacancyId,
            $portalId,
            'ext-123',
            'https://linkedin.com/123',
            'Senior PHP',
            $postedAt,
            $createdAt,
            $updatedAt
        );

        $this->assertSame($id, $source->id());
        $this->assertSame($vacancyId, $source->vacancyId());
        $this->assertSame($portalId, $source->portalId());
        $this->assertSame('ext-123', $source->externalVacancyId());
        $this->assertSame('https://linkedin.com/123', $source->externalUrl());
        $this->assertSame('Senior PHP', $source->title());
        $this->assertSame($postedAt, $source->postedAt());
        $this->assertSame($createdAt, $source->createdAt());
        $this->assertSame($updatedAt, $source->updatedAt());
        $this->assertSame([], $source->contents());
    }

    public function testReconstituteSkipsValidation(): void
    {
        $source = Source::reconstitute(
            SourceId::generate(),
            VacancyId::generate(),
            PortalId::generate(),
            null,
            '',
            'Senior PHP',
            new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2025-01-02T00:00:00+00:00'),
            new DateTimeImmutable('2025-01-03T00:00:00+00:00')
        );

        $this->assertSame('', $source->externalUrl());
    }

    public function testCreateSourceKeepsFieldsAndSetsCreationTimestamps(): void
    {
        $id = SourceId::generate();
        $vacancyId = VacancyId::generate();
        $portalId = PortalId::generate();
        $postedAt = new DateTimeImmutable('2025-01-01T00:00:00+00:00');

        $source = Source::createSource(
            $id,
            $vacancyId,
            $portalId,
            'ext-123',
            'https://linkedin.com/123',
            'Senior PHP',
            $postedAt
        );

        $this->assertSame($id, $source->id());
        $this->assertSame($vacancyId, $source->vacancyId());
        $this->assertSame($portalId, $source->portalId());
        $this->assertSame('ext-123', $source->externalVacancyId());
        $this->assertSame('https://linkedin.com/123', $source->externalUrl());
        $this->assertSame('Senior PHP', $source->title());
        $this->assertSame($postedAt, $source->postedAt());
        $this->assertSame($source->createdAt(), $source->updatedAt());
        $this->assertSame([], $source->contents());
    }

    public function testCreateWithEmptyExternalUrlThrows(): void
    {
        $this->expectException(ExternalUrlInvalidException::class);

        Source::createSource(
            SourceId::generate(),
            VacancyId::generate(),
            PortalId::generate(),
            null,
            '   ',
            'Senior PHP',
            new DateTimeImmutable()
        );
    }

    public function testRefreshUpdatesPortalTitleAndPostedAt(): void
    {
        $source = $this->source();
        $updatedAt = $source->updatedAt();
        $newPortalId = PortalId::generate();
        $newPostedAt = new DateTimeImmutable('2025-02-01T00:00:00+00:00');

        $source->refresh($newPortalId, 'Updated title', $newPostedAt);

        $this->assertSame($newPortalId, $source->portalId());
        $this->assertSame('Updated title', $source->title());
        $this->assertSame($newPostedAt, $source->postedAt());
        $this->assertNotSame($updatedAt, $source->updatedAt());
    }

    public function testAddContentAppendsAndTouchesUpdatedAt(): void
    {
        $source = $this->source();
        $updatedAt = $source->updatedAt();
        $content = $this->content($source->id());

        $source->addContent($content);

        $this->assertSame([$content], $source->contents());
        $this->assertNotSame($updatedAt, $source->updatedAt());
    }

    public function testAddContentWithSameIdThrows(): void
    {
        $source = $this->source();
        $contentId = ContentId::generate();
        $source->addContent(Content::createContent($contentId, $source->id(), ContentTypeEnum::DESCRIPTION, 'first'));

        $this->expectException(ContentNotAssignedException::class);
        $source->addContent(Content::createContent($contentId, $source->id(), ContentTypeEnum::DESCRIPTION, 'second'));
    }

    public function testUpdateContentReplacesByIdAndTouchesUpdatedAt(): void
    {
        $source = $this->source();
        $content = $this->content($source->id());
        $source->addContent($content);
        $updatedAt = $source->updatedAt();
        $replacement = Content::createContent($content->id(), $source->id(), ContentTypeEnum::DESCRIPTION, 'updated');

        $source->updateContent($replacement);

        $this->assertSame([$replacement], $source->contents());
        $this->assertNotSame($updatedAt, $source->updatedAt());
    }

    public function testUpdateContentWithUnknownIdThrows(): void
    {
        $source = $this->source();
        $source->addContent($this->content($source->id()));

        $this->expectException(ContentNotAssignedException::class);
        $source->updateContent($this->content($source->id()));
    }

    public function testRemoveContentRemovesAndTouchesUpdatedAt(): void
    {
        $source = $this->source();
        $first = $this->content($source->id());
        $second = $this->content($source->id());
        $source->addContent($first);
        $source->addContent($second);
        $updatedAt = $source->updatedAt();

        $source->removeContent($first->id());

        $this->assertSame([$second], $source->contents());
        $this->assertSame([0], array_keys($source->contents()));
        $this->assertNotSame($updatedAt, $source->updatedAt());
    }

    public function testRemoveContentWithUnknownIdThrows(): void
    {
        $source = $this->source();
        $source->addContent($this->content($source->id()));

        $this->expectException(ContentNotAssignedException::class);
        $source->removeContent(ContentId::generate());
    }

    public function testToArrayShape(): void
    {
        $source = $this->source();
        $content = $this->content($source->id(), 'Description text');
        $source->addContent($content);

        $this->assertSame(
            [
                'id' => $source->id()->value(),
                'portal_id' => $source->portalId()->value(),
                'external_vacancy_id' => 'ext-123',
                'external_url' => 'https://linkedin.com/123',
                'title' => 'Senior PHP',
                'posted_at' => '2025-01-01T00:00:00+00:00',
                'contents' => [
                    [
                        'id' => $content->id()->value(),
                        'type' => 'description',
                        'value' => 'Description text',
                    ],
                ],
            ],
            $source->toArray()
        );
    }

    private function source(): Source
    {
        return Source::reconstitute(
            SourceId::generate(),
            VacancyId::generate(),
            PortalId::generate(),
            'ext-123',
            'https://linkedin.com/123',
            'Senior PHP',
            new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2025-01-02T00:00:00+00:00'),
            new DateTimeImmutable('2025-01-03T00:00:00+00:00')
        );
    }

    private function content(SourceId $sourceId, string $value = 'Description'): Content
    {
        return Content::createContent(ContentId::generate(), $sourceId, ContentTypeEnum::DESCRIPTION, $value);
    }
}
