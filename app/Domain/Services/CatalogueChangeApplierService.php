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
    ) {}

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
                throw new UnknownMutationTypeException($mutationType ?? 'null');
        }
    }

    private function applyCreate(array $data): void
    {
        $canonicalData = $data['canonical_data'];
        $sourceProvenance = $data['source_provenance'];
        $correlationId = $data['correlation_id'] ?? null;

        // ---- Employer ----
        $employerId = isset($canonicalData['employer']['id'])
            ? EmployerId::fromString($canonicalData['employer']['id'])
            : EmployerId::generate();

        $employer = $this->employerRepository->findById($employerId);
        if ($employer === null) {
            $employer = Employer::create(
                $employerId,
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

        // ---- Requirements ----
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

        // ---- Vacancy ----
        $vacancyId = isset($data['aggregate_id'])
            ? VacancyId::fromString($data['aggregate_id'])
            : (isset($canonicalData['id'])
                ? VacancyId::fromString($canonicalData['id'])
                : VacancyId::generate()
            );

        $vacancy = Vacancy::create(
            $vacancyId,
            $employerId,
            $canonicalData['title'],
            $canonicalData['description'] ?? '',
            new Salary(
                $canonicalData['min_salary'] ?? 0,
                $canonicalData['max_salary'] ?? null,
                $canonicalData['currency'] ?? 'USD'
            ),
            $canonicalData['country'] ?? null,
            $canonicalData['city'] ?? null,
            EmploymentTypeEnum::from($canonicalData['employment_type']),
            WorkplaceEnum::from($canonicalData['workplace']),
            new DateTimeImmutable($canonicalData['posted_at']),
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

    private function applyUpdate(array $data): void
    {
        $vacancyId = VacancyId::fromString($data['aggregate_id']);
        $vacancy = $this->vacancyRepository->findById($vacancyId);
        if ($vacancy === null) {
            throw new VacancyNotFoundException($data['aggregate_id']);
        }
        if ($vacancy->version() !== $data['expected_version']) {
            throw new VersionConflictException(
                'Vacancy',
                $vacancy->id()->value(),
                $data['expected_version'],
                $vacancy->version()
            );
        }

        $canonicalData = $data['canonical_data'];

        // Update fields
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
            isset($canonicalData['employment_type']) ? EmploymentTypeEnum::from($canonicalData['employment_type']) : null,
            isset($canonicalData['workplace']) ? WorkplaceEnum::from($canonicalData['workplace']) : null,
            isset($canonicalData['posted_at']) ? new DateTimeImmutable($canonicalData['posted_at']) : null,
            isset($canonicalData['external_urls']) ? new ExternalUrls($canonicalData['external_urls']) : null,
            $canonicalData['internal_url'] ?? null
        );

        // Update source if provided
        if (isset($data['source_provenance'])) {
            $sp = $data['source_provenance'];
            $source = $this->createSource($sp, $vacancy);
            $vacancy->addSource($source);
        }

        $this->vacancyRepository->save($vacancy);
    }

    private function applyMerge(array $data): void
    {
        $targetVacancy = $this->vacancyRepository->findById(VacancyId::fromString($data['aggregate_id']));
        if ($targetVacancy === null) {
            throw new VacancyNotFoundException($data['aggregate_id']);
        }
        if ($targetVacancy->version() !== $data['expected_version']) {
            throw new VersionConflictException(
                'Vacancy',
                $targetVacancy->id()->value(),
                $data['expected_version'],
                $targetVacancy->version()
            );
        }

        $mergedIds = $data['merge_ids'];
        if (empty($mergedIds)) {
            throw new MergeListEmptyException;
        }

        $primarySource = $this->vacancyRepository->findById(VacancyId::fromString($mergedIds[0]));
        if ($primarySource === null) {
            throw new VacancyNotFoundException($mergedIds[0]);
        }

        // Merge data from primary source
        $targetVacancy->mergeFrom($primarySource, $mergedIds);

        // Close all other merged vacancies (except target)
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

    private function applyClose(array $data): void
    {
        $vacancyId = VacancyId::fromString($data['aggregate_id']);
        $vacancy = $this->vacancyRepository->findById($vacancyId);
        if ($vacancy === null) {
            throw new VacancyNotFoundException($data['aggregate_id']);
        }
        if ($vacancy->version() !== $data['expected_version']) {
            throw new VersionConflictException(
                'Vacancy',
                $vacancy->id()->value(),
                $data['expected_version'],
                $vacancy->version()
            );
        }
        $vacancy->close();
        $this->vacancyRepository->save($vacancy);
    }

    private function createSource(array $provenance, Vacancy $vacancy): VacancySource
    {
        return new VacancySource(
            VacancySourceId::generate(),
            $vacancy->id(),
            $provenance['source_key'],
            $provenance['external_vacancy_id'],
            $provenance['external_url'],
            new DateTimeImmutable,
            new DateTimeImmutable,
            null,
            $provenance['is_primary'] ?? false
        );
    }
}