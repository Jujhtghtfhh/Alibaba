<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Let the request through only when the user's role (taken from the token) is one of the given roles.
     * Usage in routes: ->middleware('role:admin')
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! in_array($request->user()?->role, $roles, true)) {
            return response()->json(['message' => 'This action is unauthorized.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
