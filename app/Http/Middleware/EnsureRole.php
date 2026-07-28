<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Allow the request only if the authenticated user has one of the given roles.
     * On mismatch, send the user to their own role's home (not a bare 403).
     * Usage: ->middleware('role:super_admin') or 'role:doctor,clinic_admin'.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user->hasAnyRole($roles)) {
            return redirect()->route($user->homeRoute());
        }

        return $next($request);
    }
}
