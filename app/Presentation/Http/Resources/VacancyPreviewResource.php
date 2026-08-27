<?php
declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Application\DTOs\VacancyPreviewData;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VacancyPreviewData
 */
final class VacancyPreviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var VacancyPreviewData $vacancy */
        $vacancy = $this->resource;

        return [
            'id' => $vacancy->id,
            'title' => $vacancy->title,
            'employer_name' => $vacancy->employerName,
            'salary_min' => $vacancy->salaryMin,
            'salary_max' => $vacancy->salaryMax,
            'currency' => $vacancy->currency,
            'location' => $this->formatLocation($vacancy->city, $vacancy->country),
            'published_at' => $vacancy->publishedAt,
        ];
    }

    private function formatLocation(?string $city, ?string $country): string
    {
        $parts = [];
        if ($city) {
            $parts[] = $city;
        }
        if ($country) {
            $parts[] = $country;
        }

        return implode(', ', $parts);
    }
}
