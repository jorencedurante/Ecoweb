<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust the reverse proxy that terminates TLS in front of the app
        // (Railway's edge does this) so Laravel honours the X-Forwarded-Proto,
        // X-Forwarded-Host and X-Forwarded-For headers and therefore detects
        // the original https:// request. Without this, form actions and other
        // generated URLs fall back to http:// behind the proxy.
        //
        // This callback can run before the config/ and .env files are loaded,
        // so only real process environment variables can be read here. The
        // same rules live in config/trustedproxy.php, which Laravel also
        // consults at request time (that path covers values defined in .env).
        //
        // Locally there is no proxy in front of the app, so nothing is
        // trusted by default and http:// development keeps working unchanged.
        $trustedProxies = env('TRUSTED_PROXIES');

        if ($trustedProxies === null || $trustedProxies === '') {
            $trustedProxies = str_starts_with((string) env('APP_URL'), 'https://')
                || env('RAILWAY_ENVIRONMENT_NAME') !== null
                    ? '*'
                    : null;
        }

        $middleware->trustProxies(at: $trustedProxies);

        $middleware->redirectUsersTo(function () {
            return route('admin.dashboard');
        });

        $middleware->alias([
            'admin.only' => \App\Http\Middleware\AdminOnly::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'account.active' => \App\Http\Middleware\EnsureAccountActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
