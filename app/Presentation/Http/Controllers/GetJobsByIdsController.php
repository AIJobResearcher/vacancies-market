<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\GetJobsByIdsUseCase;
use App\Presentation\Http\Requests\GetJobsByIdsRequest;
use App\Presentation\Http\Resources\GetJobsByIdsCollection;
use Illuminate\Http\JsonResponse;

/** @psalm-suppress UnusedClass */
final class GetJobsByIdsController extends Controller
{
    public function __construct(private readonly GetJobsByIdsUseCase $getJobsByIdsUseCase)
    {
    }

    public function __invoke(GetJobsByIdsRequest $request): JsonResponse
    {
        return (new GetJobsByIdsCollection($this->getJobsByIdsUseCase->handle($request->id())))
            ->response($request);
    }
}
