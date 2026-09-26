<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use Illuminate\Http\Resources\Attributes\Collects;

#[Collects(GetLocationsResource::class)]
final class GetLocationsCollection extends ApiCollection
{
}
