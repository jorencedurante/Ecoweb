<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = auth()->user()->role;

        if (!in_array($role, ['admin', 'super_admin'])) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
