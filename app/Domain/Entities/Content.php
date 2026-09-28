<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Enums\ContentTypeEnum;
use App\Domain\Exceptions\ValidationException\ContentValueEmptyException;
use App\Domain\ValueObjects\EntityIds\ContentId;
use App\Domain\ValueObjects\EntityIds\SourceId;

final class Content
{
    public function __construct(
        private readonly ContentId $id,
        private readonly SourceId $sourceId,
        private readonly ContentTypeEnum $type,
        private readonly string $value,
    ) {
        if (trim($value) === '') {
            throw new ContentValueEmptyException();
        }
    }

    public function id(): ContentId
    {
        return $this->id;
    }

    public function sourceId(): SourceId
    {
        return $this->sourceId;
    }

    public function type(): ContentTypeEnum
    {
        return $this->type;
    }

    public function value(): string
    {
        return $this->value;
    }

    /** @return array{id: string, type: string, value: string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'type' => $this->type->value,
            'value' => $this->value,
        ];
    }
}
