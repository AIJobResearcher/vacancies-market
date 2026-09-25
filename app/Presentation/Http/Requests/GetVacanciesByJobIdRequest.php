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

        $country = $this->nullableString($input->input('country'));
        $city = $this->nullableString($input->input('city'));

        $minSalary = $input->input('min_salary');
        $maxSalary = $input->input('max_salary');
        $page = $input->input('page');
        $perPage = $input->input('per_page');

        return new GetVacanciesByJobIdFilterDto(
            jobId: JobId::fromString($input->string('job_id')->toString()),
            employerId: $this->nullableEmployerId(),
            country: $country,
            city: $city,
            minSalary: is_numeric($minSalary) ? (int) $minSalary : null,
            maxSalary: is_numeric($maxSalary) ? (int) $maxSalary : null,
            status: $input->enum('status', VacancyStatusEnum::class),
            workplace: $input->enum('workplace', WorkplaceEnum::class),
            employmentType: $input->enum('employment_type', EmploymentTypeEnum::class),
            postedFrom: $input->date('posted_from')?->toDateTimeImmutable(),
            postedTo: $input->date('posted_to')?->toDateTimeImmutable(),
            page: is_numeric($page) ? (int) $page : null,
            perPage: is_numeric($perPage) ? (int) $perPage : null,
        );
    }

    /**
     * phpstan, without the larastan extension, reads `input()` as `mixed`.
     */
    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function nullableEmployerId(): ?EmployerId
    {
        $value = $this->safe()->input('employer_id');

        return is_string($value) ? EmployerId::fromString($value) : null;
    }
}
