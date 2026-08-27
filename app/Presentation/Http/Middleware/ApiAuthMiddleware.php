<?php

declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
