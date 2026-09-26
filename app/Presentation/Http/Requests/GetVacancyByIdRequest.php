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
     * @return array{id: string|null}
     */
    #[Override]
    public function validationData(): array
    {
        /** @var string|null $id */
        $id = $this->route('id');

        return ['id' => $id];
    }
}
