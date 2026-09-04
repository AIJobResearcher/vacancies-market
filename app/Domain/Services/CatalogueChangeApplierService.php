<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Entities\Employer;
use App\Domain\Entities\Requirement;
use App\Domain\Entities\Vacancy;
use App\Domain\Entities\VacancySource;
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
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Domain\ValueObjects\EntityIds\VacancySourceId;
use App\Domain\ValueObjects\ExternalUrls;
use App\Domain\ValueObjects\Salary;
use DateTimeImmutable;

final readonly class CatalogueChangeApplierService
{
    public function __construct(
        private EmployerRepositoryInterface $employerRepository,
        private VacancyRepositoryInterface $vacancyRepository,
        private RequirementRepositoryInterface $requirementRepository,
        private RequirementUniquenessCheckerService $uniquenessChecker,
    ) {
    }

    // TODO: Refactor mutation-command handling to typed/discriminated command objects
    // (per mutation_type); the per-line phpstan ignores below are a temporary stopgap.

    /**
     * @param array{
     *     mutation_type: 'create'|'update'|'merge'|'close',
     *     aggregate_id?: string,
     *     expected_version?: int,
     *     correlation_id?: string|null,
     *     canonical_data?: array{
     *         id?: string,
     *         employer?: array{
     *             id?: string,
     *             title: string,
     *             description?: string,
     *             website?: string,
     *             email?: string,
     *             phone?: string,
     *             logo_url?: string
     *         },
     *         requirements?: array<int, array{
     *             id?: string,
     *             title: string,
     *             description?: string,
     *             category?: string
     *         }>,
     *         title?: string,
     *         description?: string,
     *         min_salary?: int,
     *         max_salary?: int|null,
     *         currency?: string,
     *         country?: string,
     *         city?: string,
     *         employment_type?: string,
     *         workplace?: string,
     *         posted_at?: string,
     *         external_urls?: string[],
     *         internal_url?: string
     *     },
     *     source_provenance?: array{
     *         source_key: string,
     *         external_vacancy_id: string,
     *         external_url: string,
     *         is_primary?: bool
     *     },
     *     merge_ids?: string[]
     * } $commandData
     */
    public function apply(array $commandData): void
    {
        $mutationType = $commandData['mutation_type'] ?? null;

        switch ($mutationType) {
            case 'create':
                $this->applyCreate($commandData);
                break;
            case 'update':
                $this->applyUpdate($commandData);
                break;
            case 'merge':
                $this->applyMerge($commandData);
                break;
            case 'close':
                $this->applyClose($commandData);
                break;
            default:
                // @phpstan-ignore-next-line nullCoalesce.variable
                throw new UnknownMutationTypeException($mutationType ?? 'null');
        }
    }

    /**
     * @param array{
     *     mutation_type: 'create'|'update'|'merge'|'close',
     *     aggregate_id?: string,
     *     expected_version?: int,
     *     correlation_id?: string|null,
     *     canonical_data?: array{
     *         id?: string,
     *         employer?: array{
     *             id?: string,
     *             title: string,
     *             description?: string,
     *             website?: string,
     *             email?: string,
     *             phone?: string,
     *             logo_url?: string
     *         },
     *         requirements?: array<int, array{
     *             id?: string,
     *             title: string,
     *             description?: string,
     *             category?: string
     *         }>,
     *         title?: string,
     *         description?: string,
     *         min_salary?: int,
     *         max_salary?: int|null,
     *         currency?: string,
     *         country?: string,
     *         city?: string,
     *         employment_type?: string,
     *         workplace?: string,
     *         posted_at?: string,
     *         external_urls?: string[],
     *         internal_url?: string
     *     },
     *     source_provenance?: array{
     *         source_key: string,
     *         external_vacancy_id: string,
     *         external_url: string,
     *         is_primary?: bool
     *     },
     *     merge_ids?: string[]
     * } $data
     */
    private function applyCreate(array $data): void
    {
        // @phpstan-ignore-next-line offsetAccess.notFound
        $canonicalData = $data['canonical_data'];
        // @phpstan-ignore-next-line offsetAccess.notFound
        $sourceProvenance = $data['source_provenance'];
        $correlationId = $data['correlation_id'] ?? null;

        $employerId = isset($canonicalData['employer']['id'])
            ? EmployerId::fromString($canonicalData['employer']['id'])
            : EmployerId::generate();

        $employer = $this->employerRepository->findById($employerId);
        if ($employer === null) {
            $employer = Employer::create(
                $employerId,
                // @phpstan-ignore-next-line offsetAccess.notFound
                $canonicalData['employer']['title'],
                $canonicalData['employer']['description'] ?? null,
                $canonicalData['employer']['website'] ?? null,
                $canonicalData['employer']['email'] ?? null,
                $canonicalData['employer']['phone'] ?? null,
                $canonicalData['employer']['logo_url'] ?? null,
                $correlationId
            );
            $this->employerRepository->save($employer);
        }

        $requirementIds = [];
        foreach ($canonicalData['requirements'] ?? [] as $reqData) {
            if (isset($reqData['id'])) {
                $reqId = RequirementId::fromString($reqData['id']);
                $requirement = $this->requirementRepository->findById($reqId);
                if ($requirement === null) {
                    throw new RequirementNotFoundException($reqId->value());
                }
            } else {
                $this->uniquenessChecker->ensureUnique($reqData['title'], null);
                $reqId = RequirementId::generate();
                $requirement = Requirement::create(
                    $reqId,
                    $reqData['title'],
                    $reqData['description'] ?? null,
                    $reqData['category'] ?? null
                );
                $this->requirementRepository->save($requirement);
            }
            $requirementIds[] = $reqId;
        }

        $vacancyId = isset($data['aggregate_id'])
            ? VacancyId::fromString($data['aggregate_id'])
            : (isset($canonicalData['id'])
                ? VacancyId::fromString($canonicalData['id'])
                : VacancyId::generate()
            );

        $vacancy = Vacancy::create(
            $vacancyId,
            $employerId,
            // @phpstan-ignore-next-line offsetAccess.notFound
            $canonicalData['title'],
            $canonicalData['description'] ?? '',
            new Salary(
                $canonicalData['min_salary'] ?? 0,
                $canonicalData['max_salary'] ?? null,
                $canonicalData['currency'] ?? 'USD'
            ),
            $canonicalData['country'] ?? null,
            $canonicalData['city'] ?? null,
            // @phpstan-ignore-next-line offsetAccess.notFound
            EmploymentTypeEnum::from($canonicalData['employment_type']),
            // @phpstan-ignore-next-line offsetAccess.notFound
            WorkplaceEnum::from($canonicalData['workplace']),
            // @phpstan-ignore-next-line offsetAccess.notFound
            new DateTimeImmutable($canonicalData['posted_at']),
            // @phpstan-ignore-next-line offsetAccess.notFound
            new ExternalUrls($canonicalData['external_urls']),
            $canonicalData['internal_url'] ?? null,
            $correlationId
        );

        foreach ($requirementIds as $reqId) {
            $vacancy->addRequirement($reqId);
        }

        $source = $this->createSource($sourceProvenance, $vacancy);
        $vacancy->addSource($source);
        $this->vacancyRepository->save($vacancy);
    }

    /**
     * @param array{
     *     mutation_type: 'create'|'update'|'merge'|'close',
     *     aggregate_id?: string,
     *     expected_version?: int,
     *     correlation_id?: string|null,
     *     canonical_data?: array{
     *         id?: string,
     *         employer?: array{
     *             id?: string,
     *             title: string,
     *             description?: string,
     *             website?: string,
     *             email?: string,
     *             phone?: string,
     *             logo_url?: string
     *         },
     *         requirements?: array<int, array{
     *             id?: string,
     *             title: string,
     *             description?: string,
     *             category?: string
     *         }>,
     *         title?: string,
     *         description?: string,
     *         min_salary?: int,
     *         max_salary?: int|null,
     *         currency?: string,
     *         country?: string,
     *         city?: string,
     *         employment_type?: string,
     *         workplace?: string,
     *         posted_at?: string,
     *         external_urls?: string[],
     *         internal_url?: string
     *     },
     *     source_provenance?: array{
     *         source_key: string,
     *         external_vacancy_id: string,
     *         external_url: string,
     *         is_primary?: bool
     *     },
     *     merge_ids?: string[]
     * } $data
     */
    private function applyUpdate(array $data): void
    {
        // @phpstan-ignore-next-line offsetAccess.notFound
        $vacancyId = VacancyId::fromString($data['aggregate_id']);
        $vacancy = $this->vacancyRepository->findById($vacancyId);
        if ($vacancy === null) {
            // @phpstan-ignore-next-line offsetAccess.notFound
            throw new VacancyNotFoundException($data['aggregate_id']);
        }
        // @phpstan-ignore-next-line offsetAccess.notFound
        if ($vacancy->version() !== $data['expected_version']) {
            throw new VersionConflictException(
                'Vacancy',
                $vacancy->id()->value(),
                // @phpstan-ignore-next-line offsetAccess.notFound
                $data['expected_version'],
                $vacancy->version()
            );
        }

        // @phpstan-ignore-next-line offsetAccess.notFound
        $canonicalData = $data['canonical_data'];

        $vacancy->updateDetails(
            $canonicalData['title'] ?? null,
            $canonicalData['description'] ?? null,
            isset($canonicalData['min_salary']) || isset($canonicalData['max_salary']) ? new Salary(
                $canonicalData['min_salary'] ?? 0,
                $canonicalData['max_salary'] ?? null,
                $canonicalData['currency'] ?? 'USD'
            ) : null,
            $canonicalData['country'] ?? null,
            $canonicalData['city'] ?? null,
            isset($canonicalData['employment_type'])
                ? EmploymentTypeEnum::from($canonicalData['employment_type'])
                : null,
            isset($canonicalData['workplace']) ? WorkplaceEnum::from($canonicalData['workplace']) : null,
            isset($canonicalData['posted_at']) ? new DateTimeImmutable($canonicalData['posted_at']) : null,
            isset($canonicalData['external_urls']) ? new ExternalUrls($canonicalData['external_urls']) : null,
            $canonicalData['internal_url'] ?? null
        );

        if (isset($data['source_provenance'])) {
            $sp = $data['source_provenance'];
            $source = $this->createSource($sp, $vacancy);
            $vacancy->addSource($source);
        }

        $this->vacancyRepository->save($vacancy);
    }

    /**
     * @param array{
     *     mutation_type: 'create'|'update'|'merge'|'close',
     *     aggregate_id?: string,
     *     expected_version?: int,
     *     correlation_id?: string|null,
     *     canonical_data?: array{
     *         id?: string,
     *         employer?: array{
     *             id?: string,
     *             title: string,
     *             description?: string,
     *             website?: string,
     *             email?: string,
     *             phone?: string,
     *             logo_url?: string
     *         },
     *         requirements?: array<int, array{
     *             id?: string,
     *             title: string,
     *             description?: string,
     *             category?: string
     *         }>,
     *         title?: string,
     *         description?: string,
     *         min_salary?: int,
     *         max_salary?: int|null,
     *         currency?: string,
     *         country?: string,
     *         city?: string,
     *         employment_type?: string,
     *         workplace?: string,
     *         posted_at?: string,
     *         external_urls?: string[],
     *         internal_url?: string
     *     },
     *     source_provenance?: array{
     *         source_key: string,
     *         external_vacancy_id: string,
     *         external_url: string,
     *         is_primary?: bool
     *     },
     *     merge_ids?: string[]
     * } $data
     */
    private function applyMerge(array $data): void
    {
        // @phpstan-ignore-next-line offsetAccess.notFound
        $targetVacancy = $this->vacancyRepository->findById(VacancyId::fromString($data['aggregate_id']));
        if ($targetVacancy === null) {
            // @phpstan-ignore-next-line offsetAccess.notFound
            throw new VacancyNotFoundException($data['aggregate_id']);
        }
        // @phpstan-ignore-next-line offsetAccess.notFound
        if ($targetVacancy->version() !== $data['expected_version']) {
            throw new VersionConflictException(
                'Vacancy',
                $targetVacancy->id()->value(),
                // @phpstan-ignore-next-line offsetAccess.notFound
                $data['expected_version'],
                $targetVacancy->version()
            );
        }

        // @phpstan-ignore-next-line offsetAccess.notFound
        $mergedIds = $data['merge_ids'];
        if (empty($mergedIds)) {
            throw new MergeListEmptyException();
        }

        $primarySource = $this->vacancyRepository->findById(VacancyId::fromString($mergedIds[0]));
        if ($primarySource === null) {
            throw new VacancyNotFoundException($mergedIds[0]);
        }

        $targetVacancy->mergeFrom($primarySource, $mergedIds);

        foreach ($mergedIds as $mergedId) {
            $sourceId = VacancyId::fromString($mergedId);
            if (!$sourceId->equals($targetVacancy->id())) {
                $source = $this->vacancyRepository->findById($sourceId);
                if ($source) {
                    $source->close();
                    $this->vacancyRepository->save($source);
                }
            }
        }

        $this->vacancyRepository->save($targetVacancy);
    }

    /**
     * @param array{
     *     mutation_type: 'create'|'update'|'merge'|'close',
     *     aggregate_id?: string,
     *     expected_version?: int,
     *     correlation_id?: string|null,
     *     canonical_data?: array{
     *         id?: string,
     *         employer?: array{
     *             id?: string,
     *             title: string,
     *             description?: string,
     *             website?: string,
     *             email?: string,
     *             phone?: string,
     *             logo_url?: string
     *         },
     *         requirements?: array<int, array{
     *             id?: string,
     *             title: string,
     *             description?: string,
     *             category?: string
     *         }>,
     *         title?: string,
     *         description?: string,
     *         min_salary?: int,
     *         max_salary?: int|null,
     *         currency?: string,
     *         country?: string,
     *         city?: string,
     *         employment_type?: string,
     *         workplace?: string,
     *         posted_at?: string,
     *         external_urls?: string[],
     *         internal_url?: string
     *     },
     *     source_provenance?: array{
     *         source_key: string,
     *         external_vacancy_id: string,
     *         external_url: string,
     *         is_primary?: bool
     *     },
     *     merge_ids?: string[]
     * } $data
     */
    private function applyClose(array $data): void
    {
        // @phpstan-ignore-next-line offsetAccess.notFound
        $vacancyId = VacancyId::fromString($data['aggregate_id']);
        $vacancy = $this->vacancyRepository->findById($vacancyId);
        if ($vacancy === null) {
            // @phpstan-ignore-next-line offsetAccess.notFound
            throw new VacancyNotFoundException($data['aggregate_id']);
        }
        // @phpstan-ignore-next-line offsetAccess.notFound
        if ($vacancy->version() !== $data['expected_version']) {
            throw new VersionConflictException(
                'Vacancy',
                $vacancy->id()->value(),
                // @phpstan-ignore-next-line offsetAccess.notFound
                $data['expected_version'],
                $vacancy->version()
            );
        }
        $vacancy->close();
        $this->vacancyRepository->save($vacancy);
    }

    /**
     * @param array{
     *     source_key: string,
     *     external_vacancy_id: string,
     *     external_url: string,
     *     is_primary?: bool
     * } $provenance
     */
    private function createSource(array $provenance, Vacancy $vacancy): VacancySource
    {
        return new VacancySource(
            VacancySourceId::generate(),
            $vacancy->id(),
            $provenance['source_key'],
            $provenance['external_vacancy_id'],
            $provenance['external_url'],
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
            $provenance['is_primary'] ?? false
        );
    }
}
