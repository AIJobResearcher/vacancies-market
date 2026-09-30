<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\GetVacancyByIdUseCase;
use App\Domain\Exceptions\EntityNotFoundException\VacancyNotFoundException;
use App\Presentation\Http\Requests\GetVacancyByIdRequest;
use App\Presentation\Http\Resources\GetVacancyByIdResource;
use Illuminate\Http\JsonResponse;

/** @psalm-suppress UnusedClass */
final class GetVacancyByIdController extends Controller
{
    public function __construct(private readonly GetVacancyByIdUseCase $getVacancyByIdUseCase)
    {
    }

    public function __invoke(GetVacancyByIdRequest $request): JsonResponse
    {
        try {
            $vacancy = $this->getVacancyByIdUseCase->handle($request->id());
        } catch (VacancyNotFoundException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 404);
        }

        return (new GetVacancyByIdResource($vacancy))->response($request);
    }
}
