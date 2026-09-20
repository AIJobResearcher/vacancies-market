<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class GetVacancyByIdRequest extends FormRequest
{
    public function id(): string
    {
        return $this->safe()->string('id')->toString();
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function validationData(): array
    {
        return ['id' => $this->route('id')];
    }
}
