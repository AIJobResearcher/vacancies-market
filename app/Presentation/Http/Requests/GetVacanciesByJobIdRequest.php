<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use App\Application\DTOs\GetVacanciesByJobIdDto;
use App\Domain\Enums\EmploymentTypeEnum;
use App\Domain\Enums\VacancyStatusEnum;
use App\Domain\Enums\WorkplaceEnum;
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
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'employer_id' => ['nullable', 'uuid'],
            'employment_type' => ['nullable', Rule::enum(EmploymentTypeEnum::class)],
            'job_id' => ['required', 'uuid'],
            'max_salary' => ['nullable', 'integer', 'min:0'],
            'min_salary' => ['nullable', 'integer', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'posted_from' => ['nullable', 'date'],
            'posted_to' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(VacancyStatusEnum::class)],
            'workplace' => ['nullable', Rule::enum(WorkplaceEnum::class)],
        ];
    }

    public function toDto(): GetVacanciesByJobIdDto
    {
        $input = $this->safe();

        return new GetVacanciesByJobIdDto(
            jobId: $input->string('job_id')->toString(),
            employerId: $this->nullableString($input, 'employer_id'),
            country: $this->nullableString($input, 'country'),
            city: $this->nullableString($input, 'city'),
            minSalary: $this->nullableInt($input, 'min_salary'),
            maxSalary: $this->nullableInt($input, 'max_salary'),
            status: $input->enum('status', VacancyStatusEnum::class),
            workplace: $input->enum('workplace', WorkplaceEnum::class),
            employmentType: $input->enum('employment_type', EmploymentTypeEnum::class),
            postedFrom: $input->date('posted_from')?->toDateTimeImmutable(),
            postedTo: $input->date('posted_to')?->toDateTimeImmutable(),
            perPage: $this->nullableInt($input, 'per_page'),
            page: $this->nullableInt($input, 'page'),
        );
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
