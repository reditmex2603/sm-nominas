<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerPermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        if (! $request->user() || ! $request->user()->tienePermiso($permiso)) {
            abort(403);
        }

        return $next($request);
    }
}
