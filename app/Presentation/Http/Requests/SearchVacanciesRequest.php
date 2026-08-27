<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use App\Application\DTOs\SearchVacanciesCriteria;
use App\Application\Enums\VacancySort;
use App\Domain\Enums\VacancyStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SearchVacanciesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string|array>
     */
    public function rules(): array
    {
        return [
            'query' => 'nullable|string|max:255',
            'employer_id' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0|gte:salary_min',
            'status' => ['nullable', Rule::enum(VacancyStatusEnum::class)],
            'per_page' => 'nullable|integer|between:1,100',
            'page' => 'nullable|integer|min:1',
            'sort' => ['nullable', Rule::enum(VacancySort::class)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'salary_max.gte' => 'The salary_max must be greater than or equal to salary_min.',
            'per_page.between' => 'The per_page must be between 1 and 100.',
            'page.min' => 'The page must be at least 1.',
            'status.enum' => 'The status must be either "open" or "closed".',
            'sort.enum' => 'The sort must be one of: relevance, date, salary_asc, salary_desc.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'query' => $this->normalizeNullableString('query'),
            'employer_id' => $this->normalizeNullableString('employer_id'),
            'country' => $this->normalizeNullableString('country'),
            'city' => $this->normalizeNullableString('city'),
            'status' => $this->normalizeNullableString('status'),
        ]);
    }

    /**
     * Get the validated data as filters and pagination params
     *
     * @return array<string, mixed>
     */
    public function toCriteria(): SearchVacanciesCriteria
    {
        $status = $this->input('status');
        $salaryMin = $this->input('salary_min');
        $salaryMax = $this->input('salary_max');

        return new SearchVacanciesCriteria(
            query: $this->input('query'),
            employerId: $this->input('employer_id'),
            country: $this->input('country'),
            city: $this->input('city'),
            salaryMin: $salaryMin !== null ? (int) $salaryMin : null,
            salaryMax: $salaryMax !== null ? (int) $salaryMax : null,
            status: $status !== null ? VacancyStatusEnum::tryFrom($status) : null,
            perPage: $this->integer('per_page', 20),
            page: $this->integer('page', 1),
            sort: VacancySort::tryFrom((string) $this->input('sort', VacancySort::Relevance->value)) ?? VacancySort::Relevance
        );
    }

    private function normalizeNullableString(string $field): ?string
    {
        $value = $this->input($field);
        if (! is_string($value)) {
            return $value;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
