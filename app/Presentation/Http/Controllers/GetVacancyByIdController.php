<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\GetVacancyByIdUseCase;
use App\Presentation\Http\Requests\GetVacancyByIdRequest;
use App\Presentation\Http\Resources\GetVacancyByIdResource;
use Illuminate\Http\JsonResponse;

final class GetVacancyByIdController extends Controller
{
    public function __construct(private readonly GetVacancyByIdUseCase $getVacancyByIdUseCase)
    {
    }

    public function __invoke(GetVacancyByIdRequest $request): JsonResponse
    {
        return (new GetVacancyByIdResource($this->getVacancyByIdUseCase->handle($request->id())))
            ->response($request);
    }
}
