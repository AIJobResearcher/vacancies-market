<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Requirement;
use App\Domain\Exceptions\ValidationException\RequirementTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RequirementTest extends TestCase
{
    private RequirementId $id;

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
        $req = Requirement::create($this->id, 'PHP', 'Programming language', 'technical');
        $this->assertEquals('PHP', $req->title());
        $this->assertEquals('Programming language', $req->description());
        $this->assertEquals('technical', $req->category());
    }

    public function testCreateEmptyTitleThrows(): void
    {
        $this->expectException(RequirementTitleEmptyException::class);
        Requirement::create($this->id, '');
    }

    #[DataProvider('updateProvider')]
    public function testUpdate(?string $title, ?string $desc, ?string $category): void
    {
        $req = Requirement::create($this->id, 'PHP', 'Old desc', 'tech');
        $req->update($title, $desc, $category);

        if ($title !== null) {
            $this->assertEquals($title, $req->title());
        }
        if ($desc !== null) {
            $this->assertEquals($desc, $req->description());
        }
        if ($category !== null) {
            $this->assertEquals($category, $req->category());
        }
    }

    public function testUpdateWithEmptyTitleThrows(): void
    {
        $req = Requirement::create($this->id, 'PHP');
        $this->expectException(RequirementTitleEmptyException::class);
        $req->update('');
    }
}
