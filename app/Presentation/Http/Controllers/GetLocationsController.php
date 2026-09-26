<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\GetLocationsUseCase;
use App\Presentation\Http\Resources\GetLocationsCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** @psalm-suppress UnusedClass */
final class GetLocationsController extends Controller
{
    public function __construct(private readonly GetLocationsUseCase $getLocationsUseCase)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return (new GetLocationsCollection($this->getLocationsUseCase->handle()))->response($request);
    }
}
