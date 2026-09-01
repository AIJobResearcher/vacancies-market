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
use App\Domain\Exceptions\ValidationException\UnknownMutationTypeException;
use App\Domain\Exceptions\VersionConflictException;
use App\Domain\Repositories\EmployerRepositoryInterface;
use App\Domain\Repositories\RequirementRepositoryInterface;
use App\Domain\Repositories\VacancyRepositoryInterface;
use App\Domain\ValueObjects\ExternalUrls;
use App\Domain\ValueObjects\Salary;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final readonly class CatalogueChangeApplierService
{
    public function __construct(
        private EmployerRepositoryInterface $employerRepository,
        private VacancyRepositoryInterface $vacancyRepository,
        private RequirementRepositoryInterface $requirementRepository,
        private RequirementUniquenessCheckerService $uniquenessChecker
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

        // Resolve or create Employer
        $employerId = $canonicalData['employer']['id'] ?? null;
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

        // Resolve or create Requirements
        $requirementIds = [];
        foreach ($canonicalData['requirements'] ?? [] as $reqData) {
            $reqId = $reqData['id'] ?? null;
            if ($reqId === null) {
                // Create new
                $this->uniquenessChecker->ensureUnique($reqData['title']);
                $requirement = Requirement::create(
                    $reqId ?? Uuid::uuid4()->toString(),
                    $reqData['title'],
                    $reqData['description'] ?? null,
                    $reqData['category'] ?? null
                );
                $this->requirementRepository->save($requirement);
                $reqId = $requirement->id();
            } else {
                $requirement = $this->requirementRepository->findById($reqId);
                if ($requirement === null) {
                    throw new RequirementNotFoundException($reqId);
                }
            }
            $requirementIds[] = $reqId;
        }

        // Build Vacancy
        $vacancy = Vacancy::create(
            $data['aggregate_id'] ?? $canonicalData['id'] ?? Uuid::uuid4()->toString(),
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

        // Add requirements
        foreach ($requirementIds as $reqId) {
            $vacancy->addRequirement($reqId);
        }

        // Add source
        $source = new VacancySource(
            Uuid::uuid4()->toString(),
            $vacancy->id(),
            $sourceProvenance['source_key'],
            $sourceProvenance['external_vacancy_id'],
            $sourceProvenance['external_url'],
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
            $sourceProvenance['is_primary'] ?? false
        );
        $vacancy->addSource($source);

        $this->vacancyRepository->save($vacancy);
    }

    private function applyUpdate(array $data): void
    {
        $vacancy = $this->vacancyRepository->findById($data['aggregate_id']);
        if ($vacancy === null) {
            throw new VacancyNotFoundException($data['aggregate_id']);
        }
        if ($vacancy->version() !== $data['expected_version']) {
            throw new VersionConflictException('Vacancy', $vacancy->id(), $data['expected_version'], $vacancy->version());
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
            $source = new VacancySource(
                Uuid::uuid4()->toString(),
                $vacancy->id(),
                $sp['source_key'],
                $sp['external_vacancy_id'],
                $sp['external_url'],
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                null,
                $sp['is_primary'] ?? false
            );
            $vacancy->addSource($source);
        }

        $this->vacancyRepository->save($vacancy);
    }

    private function applyMerge(array $data): void
    {
        $targetVacancy = $this->vacancyRepository->findById($data['aggregate_id']);
        if ($targetVacancy === null) {
            throw new VacancyNotFoundException($data['aggregate_id']);
        }
        if ($targetVacancy->version() !== $data['expected_version']) {
            throw new VersionConflictException('Vacancy', $targetVacancy->id(), $data['expected_version'], $targetVacancy->version());
        }

        // Assume source vacancies are in $data['merge_ids']
        $mergedIds = $data['merge_ids'];
        $primarySource = $this->vacancyRepository->findById($mergedIds[0] ?? null);
        if ($primarySource === null) {
            throw new VacancyNotFoundException($mergedIds[0] ?? 'null');
        }

        // Merge data: take from primary source or combine? For simplicity, take from primary source.
        $targetVacancy->mergeFrom($primarySource, $mergedIds);

        // Close the merged source vacancies (except target)
        foreach ($mergedIds as $mergedId) {
            if ($mergedId !== $targetVacancy->id()) {
                $source = $this->vacancyRepository->findById($mergedId);
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
        $vacancy = $this->vacancyRepository->findById($data['aggregate_id']);
        if ($vacancy === null) {
            throw new VacancyNotFoundException($data['aggregate_id']);
        }
        if ($vacancy->version() !== $data['expected_version']) {
            throw new VersionConflictException('Vacancy', $vacancy->id(), $data['expected_version'], $vacancy->version());
        }
        $vacancy->close();
        $this->vacancyRepository->save($vacancy);
    }
}