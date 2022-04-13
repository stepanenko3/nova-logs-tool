<?php

namespace Stepanenko3\LogsTool\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stepanenko3\LogsTool\LogsTool;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        return app(LogsTool::class)->authorize($request)
            ? $next($request)
            : abort(403);
    }
}
