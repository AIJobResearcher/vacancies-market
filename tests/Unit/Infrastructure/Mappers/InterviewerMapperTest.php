<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Mappers;

use App\Domain\Entities\Interviewer;
use App\Domain\Entities\InterviewerVacancyAssignment;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\InterviewerId;
use App\Domain\ValueObjects\EntityIds\InterviewerVacancyAssignmentId;
use App\Domain\ValueObjects\EntityIds\VacancyId;
use App\Infrastructure\Eloquents\Mappers\InterviewerMapper;
use App\Infrastructure\Eloquents\Models\InterviewerModel;
use App\Infrastructure\Eloquents\Models\InterviewerVacancyAssignmentModel;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class InterviewerMapperTest extends TestCase
{
    public function testToEloquentMapsDomainToModel(): void
    {
        $interviewer = $this->domainInterviewer();

        $model = (new InterviewerMapper())->toEloquent($interviewer);

        $this->assertSame($interviewer->id()->value(), $model->id);
        $this->assertSame('11111111-1111-1111-1111-111111111111', $model->employer_id);
        $this->assertSame('Alice Smith', $model->full_name);
        $this->assertSame('Senior', $model->position);
        $this->assertSame(['Linkedin' => 'https://linkedin.com/in/alice'], $model->profile_urls);
        $this->assertTrue($model->is_active);
        $this->assertSame(3, $model->version);
        $this->assertNotNull($model->deleted_at);
    }

    public function testToDomainRestoresVacancyAssignmentsFromLoadedRelation(): void
    {
        $model = $this->model();
        $model->setRelation('vacancyAssignments', new Collection([$this->assignmentRow($model)]));

        $interviewer = (new InterviewerMapper())->toDomain($model);

        $this->assertSame($model->id, $interviewer->id()->value());
        $this->assertSame('Alice Smith', $interviewer->fullName());
        $this->assertSame('Senior', $interviewer->position());
        $this->assertFalse($interviewer->isActive());
        $this->assertSame(3, $interviewer->version());
        $this->assertNotNull($interviewer->deletedAt());
        $this->assertCount(1, $interviewer->getVacancyAssignments());

        $assignment = $interviewer->getVacancyAssignments()[0];
        $this->assertFalse($assignment->isActive());
        $this->assertSame('66666666-6666-6666-6666-666666666666', $assignment->vacancyId()->value());
        $this->assertSame(5, $assignment->version());
    }

    private function domainInterviewer(): Interviewer
    {
        return Interviewer::reconstitute(
            InterviewerId::fromString('99999999-9999-9999-9999-999999999999'),
            EmployerId::fromString('11111111-1111-1111-1111-111111111111'),
            'Alice Smith',
            'Senior',
            ['Linkedin' => 'https://linkedin.com/in/alice'],
            true,
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable('2025-02-01 10:00:00'),
            3,
            new DateTimeImmutable('2025-03-01 10:00:00'),
            [
                new InterviewerVacancyAssignment(
                    InterviewerVacancyAssignmentId::fromString('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
                    InterviewerId::fromString('99999999-9999-9999-9999-999999999999'),
                    VacancyId::fromString('66666666-6666-6666-6666-666666666666'),
                    new DateTimeImmutable('2025-01-05 10:00:00'),
                    new DateTimeImmutable('2025-02-05 10:00:00'),
                    5,
                ),
            ],
        );
    }

    private function model(): InterviewerModel
    {
        $model = new InterviewerModel();
        $model->id = '99999999-9999-9999-9999-999999999999';
        $model->employer_id = '11111111-1111-1111-1111-111111111111';
        $model->full_name = 'Alice Smith';
        $model->position = 'Senior';
        $model->profile_urls = ['Linkedin' => 'https://linkedin.com/in/alice'];
        $model->is_active = false;
        $model->created_at = new DateTimeImmutable('2025-01-01 10:00:00');
        $model->updated_at = new DateTimeImmutable('2025-02-01 10:00:00');
        $model->version = 3;
        $model->deleted_at = new DateTimeImmutable('2025-03-01 10:00:00');

        return $model;
    }

    private function assignmentRow(InterviewerModel $model): InterviewerVacancyAssignmentModel
    {
        $row = new InterviewerVacancyAssignmentModel();
        $row->id = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $row->interviewer_id = $model->id;
        $row->vacancy_id = '66666666-6666-6666-6666-666666666666';
        $row->assigned_at = new DateTimeImmutable('2025-01-05 10:00:00');
        $row->unassigned_at = new DateTimeImmutable('2025-02-05 10:00:00');
        $row->version = 5;

        return $row;
    }
}
