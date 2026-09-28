<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\GetVacanciesByJobIdUseCase;
use App\Presentation\Http\Requests\GetVacanciesByJobIdRequest;
use App\Presentation\Http\Resources\GetVacanciesByJobIdCollection;
use Illuminate\Http\JsonResponse;

/** @psalm-suppress UnusedClass */
final class GetVacanciesByJobIdController extends Controller
{
    public function __construct(private readonly GetVacanciesByJobIdUseCase $getVacanciesByJobIdUseCase)
    {
    }

    public function __invoke(GetVacanciesByJobIdRequest $request): JsonResponse
    {
        $paginator = $this->getVacanciesByJobIdUseCase->handle($request->toFilterDto());

        return (new GetVacanciesByJobIdCollection($paginator))->response($request);
    }
}
