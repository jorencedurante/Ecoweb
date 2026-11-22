<?php

if (!function_exists('portal_prefix')) {
    function portal_prefix(): string
    {
        $user = auth()->user();
        if ($user && method_exists($user, 'isTeacher') && $user->isTeacher()) {
            return 'teacher';
        }
        return 'admin';
    }
}

if (!function_exists('portal_route')) {
    function portal_route(string $name, $parameters = [], bool $absolute = true): string
    {
        return route(portal_prefix() . '.' . $name, $parameters, $absolute);
    }
}
