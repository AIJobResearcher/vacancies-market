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
use Illuminate\Support\ValidatedInput;
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
            'job_id' => ['required', 'uuid'],

            'max_salary' => ['nullable', 'integer', 'min:0'],
            'min_salary' => ['nullable', 'integer', 'min:0'],
            'posted_from' => ['nullable', 'date'],
            'posted_to' => ['nullable', 'date'],

            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],

            'employer_id' => ['nullable', 'uuid'],
            'employment_type' => ['nullable', Rule::enum(EmploymentTypeEnum::class)],
            'status' => ['nullable', Rule::enum(VacancyStatusEnum::class)],
            'workplace' => ['nullable', Rule::enum(WorkplaceEnum::class)],

            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toFilterDto(): GetVacanciesByJobIdFilterDto
    {
        $input = $this->safe();

        return new GetVacanciesByJobIdFilterDto(
            jobId: JobId::fromString($input->string('job_id')->toString()),
            employerId: $this->nullableEmployerId($input),
            country: $this->nullableString($input, 'country'),
            city: $this->nullableString($input, 'city'),
            minSalary: $this->nullableInt($input, 'min_salary'),
            maxSalary: $this->nullableInt($input, 'max_salary'),
            status: $input->enum('status', VacancyStatusEnum::class),
            workplace: $input->enum('workplace', WorkplaceEnum::class),
            employmentType: $input->enum('employment_type', EmploymentTypeEnum::class),
            postedFrom: $input->date('posted_from')?->toDateTimeImmutable(),
            postedTo: $input->date('posted_to')?->toDateTimeImmutable(),
            page: $this->nullableInt($input, 'page'),
            perPage: $this->nullableInt($input, 'per_page'),
        );
    }

    private function nullableEmployerId(ValidatedInput $input): ?EmployerId
    {
        $value = $input->input('employer_id');

        return is_string($value) ? EmployerId::fromString($value) : null;
    }

    private function nullableInt(ValidatedInput $input, string $key): ?int
    {
        $value = $input->input($key);

        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableString(ValidatedInput $input, string $key): ?string
    {
        $value = $input->input($key);

        return is_string($value) ? $value : null;
    }
}
