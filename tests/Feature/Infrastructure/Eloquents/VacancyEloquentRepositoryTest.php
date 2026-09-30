<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Eloquents;

use App\Domain\DTOs\GetVacanciesByJobIdFilterDto;
use App\Domain\DTOs\VacancyPreviewDto;
use App\Domain\DTOs\VacancyPreviewPageDto;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use App\Infrastructure\Eloquents\Mappers\ContentMapper;
use App\Infrastructure\Eloquents\Mappers\SourceMapper;
use App\Infrastructure\Eloquents\Mappers\VacancyMapper;
use App\Infrastructure\Eloquents\Models\EmployerModel;
use App\Infrastructure\Eloquents\Models\JobModel;
use App\Infrastructure\Eloquents\Models\PortalModel;
use App\Infrastructure\Eloquents\Models\SourceModel;
use App\Infrastructure\Eloquents\Models\VacancyModel;
use App\Infrastructure\Eloquents\Repositories\VacancyEloquentRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VacancyEloquentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private VacancyEloquentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new VacancyEloquentRepository(
            new VacancyMapper(new SourceMapper(new ContentMapper())),
        );
    }

    public function testSearchPreviewsReturnsOnlyVacanciesAssignedToRequestedJob(): void
    {
        $employer = $this->createEmployer();
        $job = $this->createJob();
        $otherJob = $this->createJob();
        $assigned = $this->createVacancy($employer, 'Assigned');
        $assignedToOtherJob = $this->createVacancy($employer, 'Assigned to other job');
        $this->createVacancy($employer, 'Not assigned');
        $this->assignToJob($assigned, $job);
        $this->assignToJob($assignedToOtherJob, $otherJob);

        $page = $this->searchPreviews($job, 1, 20);

        $this->assertSame(1, $page->total);
        $this->assertSame([$assigned->id], $this->itemIds($page->items));
        $this->assertSame('Assigned', $page->items[0]->title);
        $this->assertSame($employer->id, $page->items[0]->employerId);
        $this->assertSame($employer->title, $page->items[0]->employerTitle);
    }

    public function testSearchPreviewsFiltersByWorkplacesOverlap(): void
    {
        $employer = $this->createEmployer();
        $job = $this->createJob();
        $hybrid = $this->createVacancy($employer, 'Hybrid', ['workplaces' => ['remote', 'hybrid']]);
        $onSite = $this->createVacancy($employer, 'On site', ['workplaces' => ['on-site']]);
        $this->assignToJob($hybrid, $job);
        $this->assignToJob($onSite, $job);

        $page = $this->repository->searchPreviews(
            new GetVacanciesByJobIdFilterDto(
                jobId: JobId::fromString($job->id),
                workplaces: [WorkplaceEnum::HYBRID],
            ),
            1,
            20,
        );

        $this->assertSame(1, $page->total);
        $this->assertSame([$hybrid->id], $this->itemIds($page->items));
        $this->assertSame(['remote', 'hybrid'], $page->items[0]->workplaces);
    }

    public function testSearchPreviewsFiltersByEmploymentTypesOverlap(): void
    {
        $employer = $this->createEmployer();
        $job = $this->createJob();
        $fullTime = $this->createVacancy($employer, 'Full time', ['employment_types' => ['full-time']]);
        $partTime = $this->createVacancy($employer, 'Part time', ['employment_types' => ['part-time', 'contract']]);
        $internship = $this->createVacancy($employer, 'Internship', ['employment_types' => ['internship']]);
        $this->assignToJob($fullTime, $job);
        $this->assignToJob($partTime, $job);
        $this->assignToJob($internship, $job);

        $page = $this->repository->searchPreviews(
            new GetVacanciesByJobIdFilterDto(
                jobId: JobId::fromString($job->id),
                employmentTypes: [EmploymentTypeEnum::PART_TIME],
            ),
            1,
            20,
        );

        $this->assertSame(1, $page->total);
        $this->assertSame([$partTime->id], $this->itemIds($page->items));
        $this->assertSame(['part-time', 'contract'], $page->items[0]->employmentTypes);

        $bothPage = $this->repository->searchPreviews(
            new GetVacanciesByJobIdFilterDto(
                jobId: JobId::fromString($job->id),
                employmentTypes: [EmploymentTypeEnum::FULL_TIME, EmploymentTypeEnum::INTERNSHIP],
            ),
            1,
            20,
        );

        $this->assertSame(2, $bothPage->total);
        $this->assertSame(
            $this->sorted([$fullTime->id, $internship->id]),
            $this->sorted($this->itemIds($bothPage->items)),
        );
    }

    public function testSearchPreviewsFiltersByResearcherLocationIdsOverlap(): void
    {
        $employer = $this->createEmployer();
        $job = $this->createJob();
        $matching = $this->createVacancy($employer, 'Matching', ['researcher_location_ids' => [804, 250]]);
        $other = $this->createVacancy($employer, 'Other', ['researcher_location_ids' => [643]]);
        $this->createVacancy($employer, 'No location');
        $this->assignToJob($matching, $job);
        $this->assignToJob($other, $job);

        $page = $this->repository->searchPreviews(
            new GetVacanciesByJobIdFilterDto(
                jobId: JobId::fromString($job->id),
                locationIds: [250],
            ),
            1,
            20,
        );

        $this->assertSame(1, $page->total);
        $this->assertSame([$matching->id], $this->itemIds($page->items));
        $this->assertSame([804, 250], $page->items[0]->researcherLocationIds);
    }

    public function testSearchPreviewsFiltersByPostedAtRange(): void
    {
        $employer = $this->createEmployer();
        $job = $this->createJob();
        $portal = $this->createPortal();
        $inside = $this->createVacancy($employer, 'Inside range');
        $outside = $this->createVacancy($employer, 'Outside range');
        $withoutSources = $this->createVacancy($employer, 'Without sources');
        $this->assignToJob($inside, $job);
        $this->assignToJob($outside, $job);
        $this->assignToJob($withoutSources, $job);
        $this->addSource($inside, $portal, new DateTimeImmutable('2025-06-15 10:00:00'));
        $this->addSource($outside, $portal, new DateTimeImmutable('2025-01-10 10:00:00'));
        $this->addSource($outside, $portal, new DateTimeImmutable('2025-02-10 10:00:00'));

        $page = $this->repository->searchPreviews(
            new GetVacanciesByJobIdFilterDto(
                jobId: JobId::fromString($job->id),
                postedFrom: new DateTimeImmutable('2025-06-01 00:00:00'),
                postedTo: new DateTimeImmutable('2025-06-30 23:59:59'),
            ),
            1,
            20,
        );

        $this->assertSame(1, $page->total);
        $this->assertSame([$inside->id], $this->itemIds($page->items));
    }

    public function testSearchPreviewsFiltersByEmployerIds(): void
    {
        $firstEmployer = $this->createEmployer();
        $secondEmployer = $this->createEmployer();
        $job = $this->createJob();
        $first = $this->createVacancy($firstEmployer, 'First employer');
        $second = $this->createVacancy($secondEmployer, 'Second employer');
        $this->assignToJob($first, $job);
        $this->assignToJob($second, $job);

        $page = $this->repository->searchPreviews(
            new GetVacanciesByJobIdFilterDto(
                jobId: JobId::fromString($job->id),
                employerIds: [EmployerId::fromString($firstEmployer->id)],
            ),
            1,
            20,
        );

        $this->assertSame(1, $page->total);
        $this->assertSame([$first->id], $this->itemIds($page->items));
    }

    public function testSearchPreviewsFiltersBySalaryBounds(): void
    {
        $employer = $this->createEmployer();
        $job = $this->createJob();
        $low = $this->createVacancy($employer, 'Low', ['min_salary' => 1000, 'max_salary' => 1500]);
        $high = $this->createVacancy($employer, 'High', ['min_salary' => 3000, 'max_salary' => 5000]);
        $this->assignToJob($low, $job);
        $this->assignToJob($high, $job);

        $aboveMinimum = $this->repository->searchPreviews(
            new GetVacanciesByJobIdFilterDto(
                jobId: JobId::fromString($job->id),
                minSalary: 2500,
            ),
            1,
            20,
        );

        $this->assertSame(1, $aboveMinimum->total);
        $this->assertSame([$high->id], $this->itemIds($aboveMinimum->items));

        $belowMaximum = $this->repository->searchPreviews(
            new GetVacanciesByJobIdFilterDto(
                jobId: JobId::fromString($job->id),
                maxSalary: 1500,
            ),
            1,
            20,
        );

        $this->assertSame(1, $belowMaximum->total);
        $this->assertSame([$low->id], $this->itemIds($belowMaximum->items));
    }

    public function testSearchPreviewsFiltersByStatus(): void
    {
        $employer = $this->createEmployer();
        $job = $this->createJob();
        $open = $this->createVacancy($employer, 'Open', ['status' => VacancyStatusEnum::OPEN->value]);
        $closed = $this->createVacancy($employer, 'Closed', ['status' => VacancyStatusEnum::CLOSED->value]);
        $this->assignToJob($open, $job);
        $this->assignToJob($closed, $job);

        $openPage = $this->repository->searchPreviews(
            new GetVacanciesByJobIdFilterDto(
                jobId: JobId::fromString($job->id),
                status: VacancyStatusEnum::OPEN,
            ),
            1,
            20,
        );

        $this->assertSame(1, $openPage->total);
        $this->assertSame([$open->id], $this->itemIds($openPage->items));

        $closedPage = $this->repository->searchPreviews(
            new GetVacanciesByJobIdFilterDto(
                jobId: JobId::fromString($job->id),
                status: VacancyStatusEnum::CLOSED,
            ),
            1,
            20,
        );

        $this->assertSame(1, $closedPage->total);
        $this->assertSame([$closed->id], $this->itemIds($closedPage->items));
    }

    public function testSearchPreviewsPaginatesItemsAndKeepsFullTotal(): void
    {
        $employer = $this->createEmployer();
        $job = $this->createJob();
        $first = $this->createVacancy($employer, 'First');
        $second = $this->createVacancy($employer, 'Second');
        $third = $this->createVacancy($employer, 'Third');
        $this->assignToJob($first, $job);
        $this->assignToJob($second, $job);
        $this->assignToJob($third, $job);

        $firstPage = $this->searchPreviews($job, 1, 2);
        $secondPage = $this->searchPreviews($job, 2, 2);

        $this->assertSame(3, $firstPage->total);
        $this->assertCount(2, $firstPage->items);
        $this->assertSame(3, $secondPage->total);
        $this->assertCount(1, $secondPage->items);
        $this->assertSame(
            $this->sorted([$first->id, $second->id, $third->id]),
            $this->sorted([...$this->itemIds($firstPage->items), ...$this->itemIds($secondPage->items)]),
        );
    }

    private function searchPreviews(JobModel $job, int $page, int $perPage): VacancyPreviewPageDto
    {
        return $this->repository->searchPreviews(
            new GetVacanciesByJobIdFilterDto(jobId: JobId::fromString($job->id)),
            $page,
            $perPage,
        );
    }

    /**
     * @param array<int, VacancyPreviewDto> $items
     * @return list<string>
     */
    private function itemIds(array $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[] = $item->id;
        }

        return $ids;
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }

    private function createEmployer(): EmployerModel
    {
        return EmployerModel::factory()->create();
    }

    private function createJob(): JobModel
    {
        return JobModel::factory()->create();
    }

    private function createPortal(): PortalModel
    {
        return PortalModel::factory()->create();
    }

    /**
     * @param array<string, int|string|array<int, int|string>|null> $attributes
     */
    private function createVacancy(EmployerModel $employer, string $title, array $attributes = []): VacancyModel
    {
        return VacancyModel::factory()->create([
            'employer_id' => $employer->id,
            'title' => $title,
            ...$attributes,
        ]);
    }

    private function assignToJob(VacancyModel $vacancy, JobModel $job): void
    {
        DB::table('vacancy_job_assignments')->insert([
            'vacancy_id' => $vacancy->id,
            'job_id' => $job->id,
        ]);
    }

    private function addSource(VacancyModel $vacancy, PortalModel $portal, DateTimeImmutable $postedAt): void
    {
        SourceModel::factory()->create([
            'vacancy_id' => $vacancy->id,
            'portal_id' => $portal->id,
            'posted_at' => $postedAt,
        ]);
    }
}
