<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Content;
use App\Domain\Enums\ContentTypeEnum;
use App\Domain\Exceptions\ValidationException\ContentValueEmptyException;
use App\Domain\ValueObjects\EntityIds\ContentId;
use App\Domain\ValueObjects\EntityIds\SourceId;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContentTest extends TestCase
{
    private ContentId $contentId;

    private SourceId $sourceId;

    #[Override]
    protected function setUp(): void
    {
        $this->contentId = ContentId::generate();
        $this->sourceId = SourceId::generate();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function emptyValueProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
        ];
    }

    public function testCreateKeepsAllFields(): void
    {
        $content = Content::createContent(
            $this->contentId,
            $this->sourceId,
            ContentTypeEnum::DESCRIPTION,
            'Full description',
        );

        $this->assertSame($this->contentId, $content->id());
        $this->assertSame($this->sourceId, $content->sourceId());
        $this->assertSame('Full description', $content->value());
    }

    #[DataProvider('emptyValueProvider')]
    public function testCreateWithEmptyValueThrows(string $value): void
    {
        $this->expectException(ContentValueEmptyException::class);

        Content::createContent($this->contentId, $this->sourceId, ContentTypeEnum::DESCRIPTION, $value);
    }

    public function testReconstituteSkipsValidation(): void
    {
        $content = Content::reconstitute(
            $this->contentId,
            $this->sourceId,
            ContentTypeEnum::DESCRIPTION,
            '',
        );

        $this->assertSame('', $content->value());
    }

    public function testToArrayShape(): void
    {
        $content = Content::createContent(
            $this->contentId,
            $this->sourceId,
            ContentTypeEnum::DESCRIPTION,
            'Full description',
        );

        $this->assertSame(
            [
                'id' => $this->contentId->value(),
                'type' => 'description',
                'value' => 'Full description',
            ],
            $content->toArray()
        );
    }
}
