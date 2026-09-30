<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Mappers;

use App\Domain\Entities\Interviewer;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\InterviewerContacts;
use App\Infrastructure\Eloquents\Mappers\InterviewerMapper;
use App\Infrastructure\Eloquents\Models\InterviewerModel;
use DateTimeImmutable;
use Tests\TestCase;

final class InterviewerMapperTest extends TestCase
{
    public function testToEloquentMapsDomainToModel(): void
    {
        $interviewer = $this->domainInterviewer();

        $model = (new InterviewerMapper())->toEloquent($interviewer);

        $this->assertSame($interviewer->id()->value(), $model->id);
        $this->assertSame('Alice Smith', $model->full_name);
        $this->assertSame('Senior', $model->position);
        $this->assertSame(
            [
                ['type' => 'profile_urls', 'value' => 'https://linkedin.com/in/alice'],
                ['type' => 'email', 'value' => 'alice@example.test'],
            ],
            $model->contacts
        );
        $this->assertSame('https://avatar.test/alice.png', $model->avatar_url);
        $this->assertFalse($model->is_active);
        $this->assertSame(3, $model->version);
        $this->assertNotNull($model->deleted_at);
    }

    public function testToDomainRestoresDomainFromModel(): void
    {
        $interviewer = (new InterviewerMapper())->toDomain($this->model());

        $this->assertSame('99999999-9999-9999-9999-999999999999', $interviewer->id()->value());
        $this->assertSame('Alice Smith', $interviewer->fullName());
        $this->assertSame('Senior', $interviewer->position());
        $this->assertSame(
            [
                ['type' => 'profile_urls', 'value' => 'https://linkedin.com/in/alice'],
                ['type' => 'email', 'value' => 'alice@example.test'],
            ],
            $interviewer->contacts()?->toArray()
        );
        $this->assertSame('https://avatar.test/alice.png', $interviewer->avatarUrl());
        $this->assertFalse($interviewer->isActive());
        $this->assertSame(3, $interviewer->version());
        $this->assertNotNull($interviewer->deletedAt());
        $this->assertEquals(new DateTimeImmutable('2025-01-01 10:00:00'), $interviewer->createdAt());
        $this->assertEquals(new DateTimeImmutable('2025-02-01 10:00:00'), $interviewer->updatedAt());
    }

    private function domainInterviewer(): Interviewer
    {
        return Interviewer::reconstitute(
            InterviewerId::fromString('99999999-9999-9999-9999-999999999999'),
            'Alice Smith',
            'Senior',
            InterviewerContacts::fromArray([
                ['type' => 'profile_urls', 'value' => 'https://linkedin.com/in/alice'],
                ['type' => 'email', 'value' => 'alice@example.test'],
            ]),
            'https://avatar.test/alice.png',
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-02-01 10:00:00'),
            3,
            new DateTimeImmutable('2025-03-01 10:00:00'),
        );
    }

    private function model(): InterviewerModel
    {
        $model = new InterviewerModel();
        $model->id = '99999999-9999-9999-9999-999999999999';
        $model->employer_id = '11111111-1111-1111-1111-111111111111';
        $model->full_name = 'Alice Smith';
        $model->position = 'Senior';
        $model->contacts = [
            ['type' => 'profile_urls', 'value' => 'https://linkedin.com/in/alice'],
            ['type' => 'email', 'value' => 'alice@example.test'],
        ];
        $model->avatar_url = 'https://avatar.test/alice.png';
        $model->is_active = false;
        $model->created_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $model->updated_at = new DateTimeImmutable('2025-02-01 10:00:00');
        $model->version = 3;
        $model->deleted_at = new DateTimeImmutable('2025-03-01 10:00:00');

        return $model;
    }
}
