<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * System Settings is reserved for Principal (the internal super_admin role).
 * Ordinary Admin accounts must not view or modify it.
 */
class PrincipalOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Principal can access System Settings.');
        }

        return $next($request);
    }
}
