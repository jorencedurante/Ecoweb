<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOrSuper
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['admin', 'super_admin'], true)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
