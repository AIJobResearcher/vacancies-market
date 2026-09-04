<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Services;

use App\Domain\Entities\Employer;
use App\Domain\Entities\Requirement;
use App\Domain\Entities\Vacancy;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\Exceptions\EntityNotFoundException\RequirementNotFoundException;
use App\Domain\Exceptions\EntityNotFoundException\VacancyNotFoundException;
use App\Domain\Exceptions\ValidationException\MergeListEmptyException;
use App\Domain\Exceptions\ValidationException\UnknownMutationTypeException;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\EmployerRepositoryInterface;
use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Domain\Services\CatalogueChangeApplierService;
use App\Domain\Services\RequirementUniquenessCheckerService;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\ExternalUrls;
use App\Domain\ValueObjects\Salary;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress InvalidArgument ArgumentTypeCoercion PossiblyNullReference
 */
final class CatalogueChangeApplierServiceTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private EmployerRepositoryInterface&MockInterface $employerRepo;

    private VacancyRepositoryInterface&MockInterface $vacancyRepo;

    private RequirementRepositoryInterface&MockInterface $requirementRepo;

    private RequirementUniquenessCheckerService $uniquenessChecker;

    private CatalogueChangeApplierService $service;

    #[Override]
    protected function setUp(): void
    {
        $this->employerRepo = Mockery::mock(EmployerRepositoryInterface::class);
        $this->vacancyRepo = Mockery::mock(VacancyRepositoryInterface::class);
        $this->requirementRepo = Mockery::mock(RequirementRepositoryInterface::class);
        $this->uniquenessChecker = new RequirementUniquenessCheckerService($this->requirementRepo);
        $this->service = new CatalogueChangeApplierService(
            $this->employerRepo,
            $this->vacancyRepo,
            $this->requirementRepo,
            $this->uniquenessChecker
        );
    }

    private function createVacancy(?VacancyId $id = null): Vacancy
    {
        return Vacancy::create(
            $id ?? VacancyId::generate(),
            EmployerId::generate(),
            'Software Engineer',
            'Description',
            new Salary(1000, 2000, 'USD'),
            'USA',
            'NYC',
            EmploymentTypeEnum::FULL_TIME,
            WorkplaceEnum::REMOTE,
            new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
            new ExternalUrls(['https://example.com/vacancy'])
        );
    }

    private function createVacancyVersioned(int $version): Vacancy
    {
        $vacancy = $this->createVacancy();
        while ($vacancy->version() < $version) {
            $vacancy->updateDetails();
        }

        return $vacancy;
    }

    /**
     * @return array{
     *     mutation_type: string,
     *     aggregate_id: string,
     *     expected_version: int,
     *     canonical_data: array{title: string}
     * }
     */
    private function versionCommand(string $aggregateId, int $expectedVersion): array
    {
        return [
            'mutation_type' => 'update',
            'aggregate_id' => $aggregateId,
            'expected_version' => $expectedVersion,
            'canonical_data' => ['title' => 'Updated'],
        ];
    }

    /**
     * @return array<string, array{
     *     0: ?string,
     *     1: string,
     *     2: array<int, array{title: string}>,
     *     3: bool,
     *     4: bool
     * }>
     */
    public static function createCommandProvider(): array
    {
        return [
            'new employer and requirement' => [
                null,
                'New Employer',
                [['title' => 'PHP']],
                true,
                true,
            ],
            'existing employer' => [
                '550e8400-e29b-41d4-a716-446655440000',
                'Existing',
                [['title' => 'PHP']],
                false,
                true,
            ],
        ];
    }

    /**
     * @param array<int, array{title: string}> $requirements
     */
    #[DataProvider('createCommandProvider')]
    public function testApplyCreate(
        ?string $employerId,
        string $employerTitle,
        array $requirements,
        bool $expectNewEmployer,
        bool $expectNewRequirement
    ): void {
        $command = $this->buildCreateCommand($employerId, $employerTitle, $requirements);

        $expectedEmployerId = null;

        if ($expectNewEmployer) {
            $this->employerRepo->shouldReceive('findById')->once()->andReturn(null);
            $this->employerRepo->shouldReceive('save')->once()
                ->with(Mockery::on(
                    function (Employer $arg) use ($employerTitle, &$expectedEmployerId): bool {
                        $expectedEmployerId = $arg->id();

                        return $arg->title() === $employerTitle;
                    }
                ));
        } else {
            // @phpstan-ignore-next-line argument.type
            $existing = Employer::create(EmployerId::fromString($employerId), $employerTitle);
            $expectedEmployerId = $existing->id();
            $this->employerRepo->shouldReceive('findById')->once()->andReturn($existing);
            $this->employerRepo->shouldReceive('save')->never();
        }

        if ($expectNewRequirement) {
            $this->requirementRepo
                ->shouldReceive('findByTitleCaseInsensitive')
                ->with('PHP')
                ->once()
                ->andReturn(null);
            $this->requirementRepo->shouldReceive('save')->once()
                ->with(Mockery::on(fn (Requirement $arg) => $arg->title() === 'PHP'));
        }

        $savedVacancy = null;
        $this->vacancyRepo->shouldReceive('save')->once()
            ->with(Mockery::on(
                function (Vacancy $arg) use (&$savedVacancy): bool {
                    $savedVacancy = $arg;

                    return true;
                }
            ));

        // @phpstan-ignore-next-line argument.type
        $this->service->apply($command);

        $this->assertInstanceOf(Vacancy::class, $savedVacancy);
        $data = $savedVacancy->toArray();
        // @phpstan-ignore-next-line method.nonObject
        if ($expectedEmployerId === null) {
            throw new \LogicException('Expected employer id was not captured.');
        }
        $this->assertSame($expectedEmployerId->value(), $data['employer_id']);
        if ($expectNewRequirement) {
            $this->assertNotEmpty($data['requirements']);
        }
    }

    public function testApplyCreateRequirementNotFoundThrows(): void
    {
        $command = $this->buildCreateCommand(null, 'New Employer', [['id' => RequirementId::generate()->value()]]);

        $this->employerRepo->shouldReceive('findById')->andReturn(null);
        $this->employerRepo->shouldReceive('save')->once();

        $this->requirementRepo->shouldReceive('findById')->once()->andReturn(null);

        $this->expectException(RequirementNotFoundException::class);
        // @phpstan-ignore-next-line argument.type
        $this->service->apply($command);
    }

    public function testApplyCreateReusesExistingRequirementById(): void
    {
        $existingRequirement = Requirement::create(RequirementId::generate(), 'PHP');
        $command = $this->buildCreateCommand(null, 'New Employer', [['id' => $existingRequirement->id()->value()]]);

        $this->employerRepo->shouldReceive('findById')->andReturn(null);
        $this->employerRepo->shouldReceive('save')->once();

        $this->requirementRepo->shouldReceive('findById')->once()->andReturn($existingRequirement);
        $this->requirementRepo->shouldReceive('save')->never();

        $this->vacancyRepo->shouldReceive('save')->once()->with(Mockery::type(Vacancy::class));

        // @phpstan-ignore-next-line argument.type
        $this->service->apply($command);
    }

    public function testApplyUpdateAddsSourceWhenProvenancePresent(): void
    {
        $vacancy = $this->createVacancy();

        $this->vacancyRepo->shouldReceive('findById')->once()->andReturn($vacancy);
        $this->vacancyRepo->shouldReceive('save')->once()->with($vacancy);

        $command = [
            'mutation_type' => 'update',
            'aggregate_id' => $vacancy->id()->value(),
            'expected_version' => $vacancy->version(),
            'canonical_data' => ['title' => 'Updated'],
            'source_provenance' => [
                'source_key' => 'linkedin',
                'external_vacancy_id' => '123',
                'external_url' => 'https://linkedin.com/123',
                'is_primary' => true,
            ],
        ];

        $this->service->apply($command);
        $this->assertSame(3, $vacancy->version());
    }

    public function testApplyUpdateSuccess(): void
    {
        $vacancy = $this->createVacancy();

        $this->vacancyRepo->shouldReceive('findById')->once()->andReturn($vacancy);
        $this->vacancyRepo->shouldReceive('save')->once()->with($vacancy);

        $command = $this->versionCommand($vacancy->id()->value(), $vacancy->version());

        // @phpstan-ignore-next-line argument.type
        $this->service->apply($command);
        $this->assertSame('Updated', $vacancy->toArray()['title']);
    }

    public function testApplyUpdateVersionMismatchThrows(): void
    {
        $vacancy = $this->createVacancy();

        $this->vacancyRepo->shouldReceive('findById')->once()->andReturn($vacancy);
        $this->vacancyRepo->shouldReceive('save')->never();

        $command = $this->versionCommand($vacancy->id()->value(), $vacancy->version() + 1);

        $this->expectException(VersionConflictException::class);
        // @phpstan-ignore-next-line argument.type
        $this->service->apply($command);
    }

    public function testApplyUpdateVacancyNotFoundThrows(): void
    {
        $this->vacancyRepo->shouldReceive('findById')->once()->andReturn(null);

        $command = $this->versionCommand(VacancyId::generate()->value(), 1);

        $this->expectException(VacancyNotFoundException::class);
        // @phpstan-ignore-next-line argument.type
        $this->service->apply($command);
    }

    public function testApplyMergeSuccess(): void
    {
        $target = $this->createVacancyVersioned(3);
        $source = $this->createVacancy();

        $this->vacancyRepo->shouldReceive('findById')
            ->andReturnUsing(
                fn (VacancyId $id) => $id->value() === $target->id()->value()
                    ? $target
                    : ($id->value() === $source->id()->value() ? $source : null)
            );
        $this->vacancyRepo->shouldReceive('save')->once()->with($source);
        $this->vacancyRepo->shouldReceive('save')->once()->with($target);

        $command = [
            'mutation_type' => 'merge',
            'aggregate_id' => $target->id()->value(),
            'expected_version' => $target->version(),
            'merge_ids' => [$source->id()->value()],
        ];

        $this->service->apply($command);
        $this->assertSame('closed', $source->status());
    }

    public function testApplyMergeEmptyIdsThrows(): void
    {
        $vacancy = $this->createVacancy();

        $this->vacancyRepo->shouldReceive('findById')->once()->andReturn($vacancy);

        $command = [
            'mutation_type' => 'merge',
            'aggregate_id' => $vacancy->id()->value(),
            'expected_version' => $vacancy->version(),
            'merge_ids' => [],
        ];
        $this->expectException(MergeListEmptyException::class);
        $this->service->apply($command);
    }

    public function testApplyMergeTargetNotFoundThrows(): void
    {
        $this->vacancyRepo->shouldReceive('findById')->once()->andReturn(null);
        $command = [
            'mutation_type' => 'merge',
            'aggregate_id' => VacancyId::generate()->value(),
            'expected_version' => 1,
            'merge_ids' => [VacancyId::generate()->value()],
        ];
        $this->expectException(VacancyNotFoundException::class);
        $this->service->apply($command);
    }

    public function testApplyMergeVersionMismatchThrows(): void
    {
        $target = $this->createVacancyVersioned(3);

        $this->vacancyRepo->shouldReceive('findById')->once()->andReturn($target);
        $this->vacancyRepo->shouldReceive('save')->never();

        $command = [
            'mutation_type' => 'merge',
            'aggregate_id' => $target->id()->value(),
            'expected_version' => $target->version() + 1,
            'merge_ids' => [VacancyId::generate()->value()],
        ];

        $this->expectException(VersionConflictException::class);
        $this->service->apply($command);
    }

    public function testApplyMergePrimarySourceNotFoundThrows(): void
    {
        $target = $this->createVacancyVersioned(3);

        $this->vacancyRepo->shouldReceive('findById')
            ->andReturnUsing(fn (VacancyId $id) => $id->value() === $target->id()->value() ? $target : null);

        $missingSourceId = VacancyId::generate();
        $command = [
            'mutation_type' => 'merge',
            'aggregate_id' => $target->id()->value(),
            'expected_version' => $target->version(),
            'merge_ids' => [$missingSourceId->value()],
        ];

        $this->expectException(VacancyNotFoundException::class);
        $this->service->apply($command);
    }

    public function testApplyCloseSuccess(): void
    {
        $vacancy = $this->createVacancy();

        $this->vacancyRepo->shouldReceive('findById')->once()->andReturn($vacancy);
        $this->vacancyRepo->shouldReceive('save')->once()->with($vacancy);

        $command = [
            'mutation_type' => 'close',
            'aggregate_id' => $vacancy->id()->value(),
            'expected_version' => $vacancy->version(),
        ];

        $this->service->apply($command);
        $this->assertSame('closed', $vacancy->status());
    }

    public function testApplyCloseVersionMismatchThrows(): void
    {
        $vacancy = $this->createVacancy();

        $this->vacancyRepo->shouldReceive('findById')->once()->andReturn($vacancy);
        $this->vacancyRepo->shouldReceive('save')->never();

        $command = [
            'mutation_type' => 'close',
            'aggregate_id' => $vacancy->id()->value(),
            'expected_version' => $vacancy->version() + 1,
        ];

        $this->expectException(VersionConflictException::class);
        $this->service->apply($command);
    }

    public function testApplyCloseVacancyNotFoundThrows(): void
    {
        $this->vacancyRepo->shouldReceive('findById')->once()->andReturn(null);

        $command = [
            'mutation_type' => 'close',
            'aggregate_id' => VacancyId::generate()->value(),
            'expected_version' => 1,
        ];

        $this->expectException(VacancyNotFoundException::class);
        $this->service->apply($command);
    }

    public function testApplyUnknownMutationTypeThrows(): void
    {
        $command = ['mutation_type' => 'unknown'];
        $this->expectException(UnknownMutationTypeException::class);
        // @phpstan-ignore-next-line argument.type
        $this->service->apply($command);
    }

    /**
     * @param array<int, array{id: string}|array{title: string}> $requirements
     * @return array{
     *     mutation_type: string,
     *     correlation_id: string,
     *     canonical_data: array{
     *         title: string,
     *         description: string,
     *         min_salary: int,
     *         max_salary: int,
     *         currency: string,
     *         country: string,
     *         city: string,
     *         employment_type: string,
     *         workplace: string,
     *         posted_at: string,
     *         external_urls: string[],
     *         employer: array{id: string, title: string},
     *         requirements: array<int, array{id: string}|array{title: string}>
     *     },
     *     source_provenance: array{
     *         source_key: string,
     *         external_vacancy_id: string,
     *         external_url: string,
     *         is_primary: bool
     *     }
     * }
     */
    private function buildCreateCommand(?string $employerId, string $employerTitle, array $requirements): array
    {
        return [
            'mutation_type' => 'create',
            'correlation_id' => 'corr-123',
            'canonical_data' => [
                'title' => 'New Vacancy',
                'description' => 'Desc',
                'min_salary' => 1000,
                'max_salary' => 2000,
                'currency' => 'USD',
                'country' => 'USA',
                'city' => 'NYC',
                'employment_type' => 'full-time',
                'workplace' => 'remote',
                'posted_at' => '2025-01-01T00:00:00+00:00',
                'external_urls' => ['https://example.com'],
                'employer' => [
                    'id' => $employerId ?? EmployerId::generate()->value(),
                    'title' => $employerTitle,
                ],
                'requirements' => $requirements,
            ],
            'source_provenance' => [
                'source_key' => 'linkedin',
                'external_vacancy_id' => '123',
                'external_url' => 'https://linkedin.com/123',
                'is_primary' => true,
            ],
        ];
    }
}
