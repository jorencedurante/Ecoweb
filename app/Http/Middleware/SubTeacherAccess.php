<?php

namespace App\Http\Middleware;

use App\Services\CollaborationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies delegated Sub Teacher permissions to every teacher-portal route.
 * Other roles pass through unchanged. Unknown routes are denied for
 * Sub Teachers so new endpoints cannot accidentally leak data.
 */
class SubTeacherAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$user->isSubTeacher()) {
            return $next($request);
        }

        $service = app(CollaborationService::class);
        $routeName = (string) $request->route()?->getName();
        $suffix = Str::after($routeName, 'teacher.');

        // Always allow the dashboard and the Sub Teacher's own account settings.
        $alwaysAllowed = [
            'dashboard', 'settings.edit', 'settings.profile.update',
            'settings.password.update', 'settings.email-change.initiate',
        ];
        if (in_array($suffix, $alwaysAllowed, true)) {
            return $next($request);
        }

        if (!$service->isAssigned($user)) {
            // Only the four module landing pages may render the styled
            // unassigned notice. Every data endpoint, export and write route
            // still returns a real 403 below - no records are exposed.
            $noticePages = ['students', 'bottle-collection', 'reports', 'qrcode', 'certificate'];
            if ($request->isMethod('GET') && in_array($suffix, $noticePages, true)) {
                return $next($request);
            }

            return $this->deny($request, $service->unassignedMessage());
        }

        $permission = $service->permissionForRoute($suffix, $request->method());

        if ($permission === null || !$service->can($user, $permission)) {
            return $this->deny($request, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 403);
        }

        abort(403, $message);
    }
}
