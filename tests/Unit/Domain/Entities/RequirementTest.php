<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Requirement;
use App\Domain\Exceptions\ValidationException\RequirementTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequirementTest extends TestCase
{
    private RequirementId $id;

    #[Override]
    protected function setUp(): void
    {
        $this->id = RequirementId::generate();
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null, 2: string|null}>
     */
    public static function updateProvider(): array
    {
        return [
            'all fields' => ['Python', 'New desc', 'language'],
            'only title' => ['Python', null, null],
            'only description' => [null, 'Only desc', null],
            'only category' => [null, null, 'new-cat'],
        ];
    }

    public function testCreateValid(): void
    {
        $req = Requirement::createRequirement($this->id, 'PHP', 'Programming language', 'technical');
        $this->assertSame($this->id, $req->id());
        $this->assertEquals('PHP', $req->title());
        $this->assertEquals('Programming language', $req->description());
        $this->assertEquals('technical', $req->category());
        $this->assertSame($req->createdAt(), $req->updatedAt());
    }

    public function testCreateWithoutOptionalFieldsDefaultsToNull(): void
    {
        $req = Requirement::createRequirement($this->id, 'PHP');

        $this->assertNull($req->description());
        $this->assertNull($req->category());
    }

    public function testCreateTrimsTitle(): void
    {
        $req = Requirement::createRequirement($this->id, '  PHP  ');

        $this->assertEquals('PHP', $req->title());
    }

    public function testCreateEmptyTitleThrows(): void
    {
        $this->expectException(RequirementTitleEmptyException::class);
        Requirement::createRequirement($this->id, '');
    }

    #[DataProvider('updateProvider')]
    public function testUpdateRequirement(?string $title, ?string $desc, ?string $category): void
    {
        $req = Requirement::createRequirement($this->id, 'PHP', 'Old desc', 'tech');
        $req->updateRequirement($title, $desc, $category);

        $this->assertEquals($title ?? 'PHP', $req->title());
        $this->assertEquals($desc ?? 'Old desc', $req->description());
        $this->assertEquals($category ?? 'tech', $req->category());
    }

    public function testUpdateRequirementTrimsTitle(): void
    {
        $req = Requirement::createRequirement($this->id, 'PHP');
        $req->updateRequirement('  Python  ');

        $this->assertEquals('Python', $req->title());
    }

    public function testUpdateRequirementRefreshesUpdatedAt(): void
    {
        $req = Requirement::createRequirement($this->id, 'PHP');
        $updatedAt = $req->updatedAt();

        $req->updateRequirement('Python');

        $this->assertNotSame($updatedAt, $req->updatedAt());
    }

    public function testUpdateRequirementWithEmptyTitleThrows(): void
    {
        $req = Requirement::createRequirement($this->id, 'PHP');
        $this->expectException(RequirementTitleEmptyException::class);
        $req->updateRequirement('');
    }

    public function testReconstituteRestoresState(): void
    {
        $createdAt = new DateTimeImmutable('2025-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2025-02-01 10:00:00');

        $req = Requirement::reconstitute($this->id, 'PHP', 'Desc', 'tech', $createdAt, $updatedAt);

        $this->assertSame($this->id, $req->id());
        $this->assertEquals('PHP', $req->title());
        $this->assertEquals('Desc', $req->description());
        $this->assertEquals('tech', $req->category());
        $this->assertSame($createdAt, $req->createdAt());
        $this->assertSame($updatedAt, $req->updatedAt());
    }
}
