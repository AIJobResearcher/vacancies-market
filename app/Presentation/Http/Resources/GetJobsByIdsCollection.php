<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use Illuminate\Http\Resources\Attributes\Collects;

#[Collects(GetJobsByIdsResource::class)]
final class GetJobsByIdsCollection extends ApiCollection
{
}
