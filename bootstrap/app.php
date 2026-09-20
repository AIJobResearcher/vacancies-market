<?php

use App\Domain\Exceptions\EntityNotFoundException;
use App\Presentation\Http\Middleware\ApiRequestTimingMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.timing' => ApiRequestTimingMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (EntityNotFoundException $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            return new JsonResponse(['message' => $exception->getMessage()], 404);
        });
    })->create();
