<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Sub Teachers use the teacher-style portal but are further restricted
        // by the subteacher.access middleware and per-route permission checks.
        if (!$user || (!$user->isTeacher() && !$user->isSubTeacher())) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
