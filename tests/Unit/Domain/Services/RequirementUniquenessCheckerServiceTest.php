<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Services;

use App\Domain\Entities\Requirement;
use App\Domain\Exceptions\ValidationException\RequirementAlreadyExistsException;
use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Domain\Services\RequirementUniquenessCheckerService;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\TestCase;

final class RequirementUniquenessCheckerServiceTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private RequirementRepositoryInterface&MockInterface $repository;

    private RequirementUniquenessCheckerService $service;

    #[Override]
    protected function setUp(): void
    {
        $this->repository = Mockery::mock(RequirementRepositoryInterface::class);
        $this->service = new RequirementUniquenessCheckerService($this->repository);
    }

    public function testEnsureUniqueWhenNoExisting(): void
    {
        $this->repository
            ->shouldReceive('findByTitleCaseInsensitive')
            ->with('PHP')
            ->once()
            ->andReturn(null);

        $this->service->ensureUnique('PHP', null);
    }

    public function testEnsureUniqueWhenExistingButSameIdExcluded(): void
    {
        $existing = Requirement::create(RequirementId::generate(), 'PHP');
        $excludeId = $existing->id();

        $this->repository
            ->shouldReceive('findByTitleCaseInsensitive')
            ->with('PHP')
            ->once()
            ->andReturn($existing);

        $this->service->ensureUnique('PHP', $excludeId);
    }

    public function testEnsureUniqueWhenExistingAndNotExcludedThrows(): void
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

    public function testEnsureUniqueWhenExistingButDifferentIdExcludedThrows(): void
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
