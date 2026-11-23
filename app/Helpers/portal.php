<?php

if (!function_exists('portal_prefix')) {
    function portal_prefix(): string
    {
        $user = auth()->user();
        if ($user && method_exists($user, 'isTeacher')
            && ($user->isTeacher() || $user->isSubTeacher())) {
            return 'teacher';
        }
        return 'admin';
    }
}

if (!function_exists('effective_teacher_id')) {
    /**
     * Teacher whose existing records the current user may access.
     * Null for admins and for Sub Teachers without an effective assignment.
     */
    function effective_teacher_id(): ?int
    {
        $user = auth()->user();

        return $user ? $user->scopeTeacherId() : null;
    }
}

if (!function_exists('portal_route')) {
    function portal_route(string $name, $parameters = [], bool $absolute = true): string
    {
        return route(portal_prefix() . '.' . $name, $parameters, $absolute);
    }
}
