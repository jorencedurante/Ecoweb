<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

view()->share('errors', new Illuminate\Support\ViewErrorBag());

$user = App\Models\User::where('role', 'super_admin')->first() ?: App\Models\User::first();
auth()->setUser($user);

$paths = [
    '/admin/dashboard',
    '/admin/students',
    '/admin/students/archived',
    '/admin/students/Q001/info',
    '/admin/bottle-collection',
    '/admin/certificate',
    '/admin/certificate/create',
    '/admin/claims',
    '/admin/reports',
    '/admin/student-report',
    '/admin/bottle-report',
    '/admin/admin-activities',
    '/admin/teachers',
    '/admin/system-settings',
    '/admin/settings',
    '/admin/qrcode',
    '/account/email-change/verify',
];

foreach ($paths as $path) {
    $request = Illuminate\Http\Request::create($path, 'GET');
    app()->instance('request', $request);
    $request->setRouteResolver(function () use ($request) {
        try {
            return Illuminate\Support\Facades\Route::getRoutes()->match($request);
        } catch (Throwable $e) {
            return null;
        }
    });

    $html = view('layouts.admin')->render();

    preg_match_all('/<a href="([^"]*)" class="nav-item([^"]*)"([^>]*)>/', $html, $m, PREG_SET_ORDER);
    $active = [];
    foreach ($m as $match) {
        $is = strpos($match[2], ' active') !== false || preg_match('/\bactive\b/', $match[2]) || strpos($match[3], 'aria-current') !== false;
        if ($is) {
            $active[] = str_replace('http://localhost', '', $match[1]);
        }
    }
    printf("%-35s active: %s\n", $path, $active ? implode(', ', $active) : '(none)');
}
