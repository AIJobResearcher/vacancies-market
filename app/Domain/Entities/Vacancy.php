<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\Exceptions\StateConflictException\JobAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\JobNotAssignedException;
use App\Domain\Exceptions\StateConflictException\RequirementAlreadyAssignedException;
use App\Domain\Exceptions\StateConflictException\RequirementNotAssignedException;
use App\Domain\Exceptions\StateConflictException\SourceNotAssignedException;
use App\Domain\Exceptions\StateConflictException\VacancyAlreadyClosedException;
use App\Domain\Exceptions\StateConflictException\VacancyAlreadyOpenException;
use App\Domain\Exceptions\ValidationException\SalaryMaxLessThanMinException;
use App\Domain\Exceptions\ValidationException\SalaryMaxNegativeException;
use App\Domain\Exceptions\ValidationException\SalaryMinNegativeException;
use App\Domain\Exceptions\ValidationException\VacancyRequiresSourceException;
use App\Domain\Exceptions\ValidationException\VacancyTitleEmptyException;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Domain\ValueObjects\EntityIds\RequirementId;
use App\Domain\ValueObjects\EntityIds\SourceId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use DateTimeImmutable;

final class Vacancy
{
    /**
     * @param list<EmploymentTypeEnum> $employmentTypes
     * @param list<WorkplaceEnum> $workplaces
     * @param list<int> $researcherLocationIds
     * @param list<RequirementId> $requirementIds
     * @param list<JobId> $jobIds
     * @param list<Source> $sources
     */
    private function __construct(
        private readonly VacancyId $id,
        private readonly EmployerId $employerId,
        private string $title,
        private int $minSalary,
        private ?int $maxSalary,
        private VacancyStatusEnum $status,
        private array $employmentTypes,
        private array $workplaces,
        private array $researcherLocationIds,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $closedAt,
        private int $version,
        private array $requirementIds = [],
        private array $jobIds = [],
        private array $sources = []
    ) {
    }

    /**
     * Hydration entry point: restores a persisted aggregate as-is.
     * Does NOT run business validation and does NOT apply creation defaults.
     * Infrastructure mappers are the only intended callers.
     *
     * @param list<EmploymentTypeEnum> $employmentTypes
     * @param list<WorkplaceEnum> $workplaces
     * @param list<int> $researcherLocationIds
     * @param list<RequirementId> $requirementIds
     * @param list<JobId> $jobIds
     * @param list<Source> $sources
     */
    public static function reconstitute(
        VacancyId $id,
        EmployerId $employerId,
        string $title,
        int $minSalary,
        ?int $maxSalary,
        VacancyStatusEnum $status,
        array $employmentTypes,
        array $workplaces,
        array $researcherLocationIds,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $closedAt,
        int $version,
        array $requirementIds = [],
        array $jobIds = [],
        array $sources = []
    ): self {
        return new self(
            $id,
            $employerId,
            $title,
            $minSalary,
            $maxSalary,
            $status,
            $employmentTypes,
            $workplaces,
            $researcherLocationIds,
            $createdAt,
            $updatedAt,
            $closedAt,
            $version,
            $requirementIds,
            $jobIds,
            $sources
        );
    }

    /**
     * @param list<EmploymentTypeEnum> $employmentTypes
     * @param list<WorkplaceEnum> $workplaces
     * @param list<int> $researcherLocationIds
     */
    public static function create(
        VacancyId $id,
        EmployerId $employerId,
        string $title,
        int $minSalary = 0,
        ?int $maxSalary = null,
        array $employmentTypes = [],
        array $workplaces = [],
        array $researcherLocationIds = [],
    ): self {
        if (trim($title) === '') {
            throw new VacancyTitleEmptyException();
        }

        self::assertSalary($minSalary, $maxSalary);

        $now = new DateTimeImmutable();

        return new self(
            $id,
            $employerId,
            trim($title),
            $minSalary,
            $maxSalary,
            VacancyStatusEnum::OPEN,
            $employmentTypes,
            $workplaces,
            $researcherLocationIds,
            $now,
            $now,
            null,
            1
        );
    }

    /**
     * @param list<EmploymentTypeEnum>|null $employmentTypes
     * @param list<WorkplaceEnum>|null $workplaces
     * @param list<int>|null $researcherLocationIds
     */
    public function updateDetails(
        ?string $title = null,
        ?int $minSalary = null,
        ?int $maxSalary = null,
        ?array $employmentTypes = null,
        ?array $workplaces = null,
        ?array $researcherLocationIds = null
    ): void {
        if ($title !== null && trim($title) === '') {
            throw new VacancyTitleEmptyException();
        }

        $minSalary ??= $this->minSalary;
        $maxSalary ??= $this->maxSalary;
        self::assertSalary($minSalary, $maxSalary);

        $this->title = $title !== null ? trim($title) : $this->title;
        $this->minSalary = $minSalary;
        $this->maxSalary = $maxSalary;
        $this->employmentTypes = $employmentTypes ?? $this->employmentTypes;
        $this->workplaces = $workplaces ?? $this->workplaces;
        $this->researcherLocationIds = $researcherLocationIds ?? $this->researcherLocationIds;

        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function close(): void
    {
        if ($this->status === VacancyStatusEnum::CLOSED) {
            throw new VacancyAlreadyClosedException($this->id->value());
        }
        $this->status = VacancyStatusEnum::CLOSED;
        $this->closedAt = new DateTimeImmutable();
        $this->updatedAt = $this->closedAt;
        $this->version++;
    }

    /**
     * Reopens a closed vacancy. Only an approved external change may
     * trigger this; it is never invoked by a local command of this context.
     */
    public function reopen(): void
    {
        if ($this->status === VacancyStatusEnum::OPEN) {
            throw new VacancyAlreadyOpenException($this->id->value());
        }
        $this->status = VacancyStatusEnum::OPEN;
        $this->closedAt = null;
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function mergeFrom(Vacancy $other): void
    {
        // Take canonical fields from $other (the source of truth after merge)
        $this->title = $other->title;
        $this->minSalary = $other->minSalary;
        $this->maxSalary = $other->maxSalary;
        $this->employmentTypes = $other->employmentTypes;
        $this->workplaces = $other->workplaces;
        $this->researcherLocationIds = $other->researcherLocationIds;

        foreach ($other->requirementIds as $requirementId) {
            if (! $this->hasRequirement($requirementId)) {
                $this->attachRequirement($requirementId);
            }
        }

        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function addRequirement(RequirementId $requirementId): void
    {
        if ($this->hasRequirement($requirementId)) {
            throw new RequirementAlreadyAssignedException($requirementId->value());
        }

        $this->attachRequirement($requirementId);
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function removeRequirement(RequirementId $requirementId): void
    {
        foreach ($this->requirementIds as $key => $assignedId) {
            if ($assignedId->equals($requirementId)) {
                unset($this->requirementIds[$key]);
                $this->requirementIds = array_values($this->requirementIds);
                $this->updatedAt = new DateTimeImmutable();
                $this->version++;

                return;
            }
        }

        throw new RequirementNotAssignedException($requirementId->value());
    }

    public function assignToJob(JobId $jobId): void
    {
        if ($this->hasJob($jobId)) {
            throw new JobAlreadyAssignedException($jobId->value());
        }

        $this->jobIds[] = $jobId;
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function unassignFromJob(JobId $jobId): void
    {
        foreach ($this->jobIds as $key => $assignedId) {
            if ($assignedId->equals($jobId)) {
                unset($this->jobIds[$key]);
                $this->jobIds = array_values($this->jobIds);
                $this->updatedAt = new DateTimeImmutable();
                $this->version++;

                return;
            }
        }

        throw new JobNotAssignedException($jobId->value());
    }

    public function addSource(Source $source): void
    {
        foreach ($this->sources as $existing) {
            if ($existing->externalUrl() === $source->externalUrl()) {
                $existing->refresh($source->portalId(), $source->title(), $source->postedAt());
                $this->updatedAt = new DateTimeImmutable();
                $this->version++;

                return;
            }
        }
        $this->sources[] = $source;
        $this->updatedAt = new DateTimeImmutable();
        $this->version++;
    }

    public function updateSource(Source $source): void
    {
        foreach ($this->sources as $key => $existing) {
            if ($existing->id()->equals($source->id())) {
                $this->sources[$key] = $source;
                $this->updatedAt = new DateTimeImmutable();
                $this->version++;

                return;
            }
        }

        throw new SourceNotAssignedException($source->id()->value());
    }

    public function removeSource(SourceId $sourceId): void
    {
        foreach ($this->sources as $key => $existing) {
            if ($existing->id()->equals($sourceId)) {
                if (count($this->sources) === 1) {
                    throw new VacancyRequiresSourceException($this->id->value());
                }
                unset($this->sources[$key]);
                $this->sources = array_values($this->sources);
                $this->updatedAt = new DateTimeImmutable();
                $this->version++;

                return;
            }
        }

        throw new SourceNotAssignedException($sourceId->value());
    }

    public function id(): VacancyId
    {
        return $this->id;
    }

    public function employerId(): EmployerId
    {
        return $this->employerId;
    }

    public function status(): string
    {
        return $this->status->value;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function minSalary(): int
    {
        return $this->minSalary;
    }

    public function maxSalary(): ?int
    {
        return $this->maxSalary;
    }

    /** @return list<EmploymentTypeEnum> */
    public function employmentTypes(): array
    {
        return $this->employmentTypes;
    }

    /** @return list<WorkplaceEnum> */
    public function workplaces(): array
    {
        return $this->workplaces;
    }

    /** @return list<int> */
    public function researcherLocationIds(): array
    {
        return $this->researcherLocationIds;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function closedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    /** @return list<RequirementId> */
    public function requirementIds(): array
    {
        return $this->requirementIds;
    }

    /** @return list<JobId> */
    public function jobIds(): array
    {
        return $this->jobIds;
    }

    /** @return list<Source> */
    public function sources(): array
    {
        return $this->sources;
    }

    /**
     * @return array{
     *     id: string,
     *     employer_id: string,
     *     title: string,
     *     min_salary: int,
     *     max_salary: int|null,
     *     status: string,
     *     employment_types: list<string>,
     *     workplaces: list<string>,
     *     researcher_location_ids: list<int>,
     *     created_at: string,
     *     updated_at: string,
     *     closed_at: string|null,
     *     version: int,
     *     requirements: string[],
     *     jobs: string[],
     *     sources: list<array{
     *         id: string,
     *         portal_id: string,
     *         external_vacancy_id: string|null,
     *         external_url: string,
     *         title: string,
     *         posted_at: string,
     *         contents: list<array{id: string, type: string, value: string}>
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'employer_id' => $this->employerId->value(),
            'title' => $this->title,
            'min_salary' => $this->minSalary,
            'max_salary' => $this->maxSalary,
            'status' => $this->status->value,
            'employment_types' => array_map(
                static fn (EmploymentTypeEnum $type): string => $type->value,
                $this->employmentTypes,
            ),
            'workplaces' => array_map(
                static fn (WorkplaceEnum $workplace): string => $workplace->value,
                $this->workplaces,
            ),
            'researcher_location_ids' => $this->researcherLocationIds,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'updated_at' => $this->updatedAt->format(DATE_ATOM),
            'closed_at' => $this->closedAt?->format(DATE_ATOM),
            'version' => $this->version,
            'requirements' => array_map(
                static fn (RequirementId $requirementId): string => $requirementId->value(),
                $this->requirementIds,
            ),
            'jobs' => array_map(
                static fn (JobId $jobId): string => $jobId->value(),
                $this->jobIds,
            ),
            'sources' => array_map(
                static fn (Source $source): array => $source->toArray(),
                $this->sources,
            ),
        ];
    }

    private static function assertSalary(int $minSalary, ?int $maxSalary): void
    {
        if ($minSalary < 0) {
            throw new SalaryMinNegativeException($minSalary);
        }

        if ($maxSalary === null) {
            return;
        }

        if ($maxSalary < 0) {
            throw new SalaryMaxNegativeException($maxSalary);
        }

        if ($maxSalary < $minSalary) {
            throw new SalaryMaxLessThanMinException($maxSalary, $minSalary);
        }
    }

    private function attachRequirement(RequirementId $requirementId): void
    {
        $this->requirementIds[] = $requirementId;
    }

    private function hasRequirement(RequirementId $requirementId): bool
    {
        foreach ($this->requirementIds as $assignedId) {
            if ($assignedId->equals($requirementId)) {
                return true;
            }
        }

        return false;
    }

    private function hasJob(JobId $jobId): bool
    {
        foreach ($this->jobIds as $assignedId) {
            if ($assignedId->equals($jobId)) {
                return true;
            }
        }

        return false;
    }
}
