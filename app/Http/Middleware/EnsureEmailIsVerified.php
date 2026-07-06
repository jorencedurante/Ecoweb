<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('info', 'Please verify your email address before accessing the dashboard.');
        }

        return $next($request);
    }
}
