<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Mappers;

use App\Domain\Entities\Employer;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Infrastructure\Eloquents\Mappers\EmployerMapper;
use App\Infrastructure\Eloquents\Models\EmployerModel;
use DateTimeImmutable;
use Tests\TestCase;

final class EmployerMapperTest extends TestCase
{
    public function testToEloquentMapsDomainToModel(): void
    {
        $employer = $this->domainEmployer();

        $model = (new EmployerMapper())->toEloquent($employer);

        $this->assertSame($employer->id()->value(), $model->id);
        $this->assertSame('Acme', $model->title);
        $this->assertSame('Description', $model->description);
        $this->assertSame('https://acme.test', $model->website);
        $this->assertSame('hr@acme.test', $model->email);
        $this->assertSame('+123', $model->phone);
        $this->assertSame('https://acme.test/logo.png', $model->logo_url);
        $this->assertSame(3, $model->version);
    }

    public function testToDomainRestoresDomainFromModel(): void
    {
        $employer = (new EmployerMapper())->toDomain($this->model());

        $this->assertSame('11111111-1111-1111-1111-111111111111', $employer->id()->value());
        $this->assertSame('Acme', $employer->title());
        $this->assertSame('Description', $employer->description());
        $this->assertSame('https://acme.test', $employer->website());
        $this->assertSame('hr@acme.test', $employer->email());
        $this->assertSame('+123', $employer->phone());
        $this->assertSame('https://acme.test/logo.png', $employer->logoUrl());
        $this->assertSame(3, $employer->version());
        $this->assertEquals(new DateTimeImmutable('2025-01-01 10:00:00'), $employer->createdAt());
        $this->assertEquals(new DateTimeImmutable('2025-02-01 10:00:00'), $employer->updatedAt());
    }

    private function domainEmployer(): Employer
    {
        return Employer::reconstitute(
            EmployerId::fromString('11111111-1111-1111-1111-111111111111'),
            'Acme',
            'Description',
            'https://acme.test',
            'hr@acme.test',
            '+123',
            'https://acme.test/logo.png',
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-02-01 10:00:00'),
            3,
        );
    }

    private function model(): EmployerModel
    {
        $model = new EmployerModel();
        $model->id = '11111111-1111-1111-1111-111111111111';
        $model->title = 'Acme';
        $model->description = 'Description';
        $model->website = 'https://acme.test';
        $model->email = 'hr@acme.test';
        $model->phone = '+123';
        $model->logo_url = 'https://acme.test/logo.png';
        $model->created_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $model->updated_at = new DateTimeImmutable('2025-02-01 10:00:00');
        $model->version = 3;

        return $model;
    }
}
