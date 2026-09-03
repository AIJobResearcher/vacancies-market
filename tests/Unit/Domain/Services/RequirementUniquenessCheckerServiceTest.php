<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Services;

use App\Domain\Entities\Requirement;
use App\Domain\Exceptions\ValidationException\RequirementAlreadyExistsException;
use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Domain\Services\RequirementUniquenessCheckerService;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use Mockery;
use PHPUnit\Framework\TestCase;

class RequirementUniquenessCheckerServiceTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private RequirementRepositoryInterface $repository;

    private RequirementUniquenessCheckerService $service;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(RequirementRepositoryInterface::class);
        $this->service = new RequirementUniquenessCheckerService($this->repository);
    }

    public function test_ensure_unique_when_no_existing(): void
    {
        $this->repository
            ->shouldReceive('findByTitleCaseInsensitive')
            ->with('PHP')
            ->once()
            ->andReturn(null);

        $this->service->ensureUnique('PHP', null);
        $this->assertTrue(true);
    }

    public function test_ensure_unique_when_existing_but_same_id_excluded(): void
    {
        $existing = Requirement::create(RequirementId::generate(), 'PHP');
        $excludeId = $existing->id();

        $this->repository
            ->shouldReceive('findByTitleCaseInsensitive')
            ->with('PHP')
            ->once()
            ->andReturn($existing);

        $this->service->ensureUnique('PHP', $excludeId);
        $this->assertTrue(true);
    }

    public function test_ensure_unique_when_existing_and_not_excluded_throws(): void
    {
        $existing = Requirement::create(RequirementId::generate(), 'PHP');

        $this->repository
            ->shouldReceive('findByTitleCaseInsensitive')
            ->with('PHP')
            ->once()
            ->andReturn($existing);

        $this->expectException(RequirementAlreadyExistsException::class);
        $this->service->ensureUnique('PHP', null);
    }

    public function test_ensure_unique_when_existing_but_different_id_excluded_throws(): void
    {
        $existing = Requirement::create(RequirementId::generate(), 'PHP');
        $otherId = RequirementId::generate();

        $this->repository
            ->shouldReceive('findByTitleCaseInsensitive')
            ->with('PHP')
            ->once()
            ->andReturn($existing);

        $this->expectException(RequirementAlreadyExistsException::class);
        $this->service->ensureUnique('PHP', $otherId);
    }
}
