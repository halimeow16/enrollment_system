<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->user_type, $types, true)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
