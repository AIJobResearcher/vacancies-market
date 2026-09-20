<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\GetVacanciesByJobIdUseCase;
use App\Presentation\Http\Requests\GetVacanciesByJobIdRequest;
use App\Presentation\Http\Resources\GetVacanciesByJobIdCollection;
use Illuminate\Http\JsonResponse;

final class GetVacanciesByJobIdController extends Controller
{
    public function __construct(private readonly GetVacanciesByJobIdUseCase $getListVacanciesUseCase)
    {
    }

    public function __invoke(GetVacanciesByJobIdRequest $request): JsonResponse
    {
        $paginator = $this->getListVacanciesUseCase->handle($request->toDto())
            ->withPath($request->url())
            ->appends($request->query());

        return (new GetVacanciesByJobIdCollection($paginator))->response($request);
    }
}
