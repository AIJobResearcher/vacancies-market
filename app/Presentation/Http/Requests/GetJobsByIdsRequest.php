<?php

declare(strict_types=1);

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetJobsByIdsRequest extends FormRequest
{
    /**
     * @return list<string>
     */
    public function id(): array
    {
        /** @var list<string> $jobIds */
        $jobIds = $this->safe()->array('job_ids');

        return $jobIds;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'job_ids' => ['required', 'array', 'distinct'],
            'job_ids.*' => ['required', 'string', 'uuid'],
        ];
    }
}
