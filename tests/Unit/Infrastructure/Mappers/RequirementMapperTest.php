<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Mappers;

use App\Domain\Entities\Requirement;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Infrastructure\Eloquents\Mappers\RequirementMapper;
use App\Infrastructure\Eloquents\Models\RequirementModel;
use DateTimeImmutable;
use Tests\TestCase;

final class RequirementMapperTest extends TestCase
{
    public function testToEloquentMapsDomainToModel(): void
    {
        $requirement = $this->domainRequirement();

        $model = (new RequirementMapper())->toEloquent($requirement);

        $this->assertSame($requirement->id()->value(), $model->id);
        $this->assertSame('PHP', $model->title);
        $this->assertSame('PHP 8.5', $model->description);
        $this->assertSame('technical', $model->category);
    }

    public function testToDomainRestoresDomainFromModel(): void
    {
        $requirement = (new RequirementMapper())->toDomain($this->model());

        $this->assertSame('88888888-8888-8888-8888-888888888888', $requirement->id()->value());
        $this->assertSame('PHP', $requirement->title());
        $this->assertSame('PHP 8.5', $requirement->description());
        $this->assertSame('technical', $requirement->category());
        $this->assertEquals(new DateTimeImmutable('2025-01-01 10:00:00'), $requirement->createdAt());
    }

    private function domainRequirement(): Requirement
    {
        return Requirement::reconstitute(
            RequirementId::fromString('88888888-8888-8888-8888-888888888888'),
            'PHP',
            'PHP 8.5',
            'technical',
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-01-02 10:00:00'),
        );
    }

    private function model(): RequirementModel
    {
        $model = new RequirementModel();
        $model->id = '88888888-8888-8888-8888-888888888888';
        $model->title = 'PHP';
        $model->description = 'PHP 8.5';
        $model->category = 'technical';
        $model->created_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $model->updated_at = new DateTimeImmutable('2025-01-02 10:00:00');

        return $model;
    }
}
