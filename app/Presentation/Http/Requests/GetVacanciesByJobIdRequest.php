<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use App\Domain\DTOs\GetVacanciesByJobIdFilterDto;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
use App\Domain\ValueObjects\EntityIds\EmployerId;
use App\Domain\ValueObjects\EntityIds\JobId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Stringable;

final class GetVacanciesByJobIdRequest extends FormRequest
{
    /**
     * @return array<string, list<string|Stringable>>
     */
    public function rules(): array
    {
        return [
            'job_id' => ['required', 'string', 'uuid'],

            'employer_ids' => ['nullable', 'array'],
            'employer_ids.*' => ['string', 'uuid'],

            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer'],

            'min_salary' => ['nullable', 'integer', 'min:0'],
            'max_salary' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::enum(VacancyStatusEnum::class)],

            'workplaces' => ['nullable', 'array'],
            'workplaces.*' => ['string', Rule::enum(WorkplaceEnum::class)],

            'employment_types' => ['nullable', 'array'],
            'employment_types.*' => ['string', Rule::enum(EmploymentTypeEnum::class)],

            'posted_from' => ['nullable', 'date'],
            'posted_to' => ['nullable', 'date'],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toFilterDto(): GetVacanciesByJobIdFilterDto
    {
        $input = $this->safe();

        return new GetVacanciesByJobIdFilterDto(
            jobId: JobId::fromString($input->string('job_id')->toString()),
            employerIds: $this->employerIds(),
            locationIds: $this->locationIds(),
            minSalary: $this->nullableInt($input->input('min_salary')),
            maxSalary: $this->nullableInt($input->input('max_salary')),
            status: $input->enum('status', VacancyStatusEnum::class),
            workplaces: $this->workplaces(),
            employmentTypes: $this->employmentTypes(),
            postedFrom: $input->date('posted_from')?->toDateTimeImmutable(),
            postedTo: $input->date('posted_to')?->toDateTimeImmutable(),
            page: $this->nullableInt($input->input('page')),
            perPage: $this->nullableInt($input->input('per_page')),
        );
    }

    /**
     * @return list<EmployerId>
     */
    private function employerIds(): array
    {
        /** @var list<string> $values */
        $values = $this->safe()->array('employer_ids');

        return array_map(static fn (string $value): EmployerId => EmployerId::fromString($value), $values);
    }

    /**
     * @return list<EmploymentTypeEnum>
     */
    private function employmentTypes(): array
    {
        /** @var list<string> $values */
        $values = $this->safe()->array('employment_types');

        return array_map(static fn (string $value): EmploymentTypeEnum => EmploymentTypeEnum::from($value), $values);
    }

    /**
     * @return list<int>
     */
    private function locationIds(): array
    {
        /** @var list<int|string> $values */
        $values = $this->safe()->array('location_ids');

        return array_map(static fn (int|string $value): int => (int) $value, $values);
    }

    /**
     * phpstan, without the larastan extension, reads `input()` as `mixed`.
     */
    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return list<WorkplaceEnum>
     */
    private function workplaces(): array
    {
        /** @var list<string> $values */
        $values = $this->safe()->array('workplaces');

        return array_map(static fn (string $value): WorkplaceEnum => WorkplaceEnum::from($value), $values);
    }
}
