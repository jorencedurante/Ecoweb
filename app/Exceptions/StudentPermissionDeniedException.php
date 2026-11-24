<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;

/**
 * Student-workflow permission denial. Authorization is unchanged; this only
 * controls presentation so the user never lands on the generic 403 page.
 */
class StudentPermissionDeniedException extends RuntimeException
{
    public function __construct(public string $action = 'add')
    {
        parent::__construct(self::messageFor($action));
    }

    public static function messageFor(string $action): string
    {
        $contact = 'Please contact your Teacher or Principal to request access.';

        return match ($action) {
            'add' => "You don't have permission to add students. {$contact}",
            'edit' => "You don't have permission to edit students. {$contact}",
            'delete' => "You don't have permission to delete students. {$contact}",
            default => "You don't have permission to perform this action. {$contact}",
        };
    }

    public function render(Request $request)
    {
        $payload = [
            'error' => $this->getMessage(),
            'message' => $this->getMessage(),
            'permission_denied' => true,
            'action' => $this->action,
        ];

        if ($request->expectsJson()
            || $request->ajax()
            || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json($payload, 403);
        }

        return redirect()->back()
            ->withInput()
            ->with('permission_denied', $payload);
    }
}
