<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Shared UI for unassigned Sub Teachers. Returns null when the user is
     * allowed to continue to the module, so callers must still rely on the
     * existing middleware/authorization for all data and write endpoints.
     */
    protected function unassignedSubTeacherNotice(string $module)
    {
        $user = auth()->user();
        if ($user && $user->isSubTeacher() && !$user->hasEffectiveAssignment()) {
            return view('pages.unassigned-sub-teacher-notice', ['module' => $module]);
        }

        return null;
    }

    protected function getQuarterDateRange(?string $quarter): array
    {
        $now = now();

        if (!$quarter) {
            return [null, null];
        }

        if ($quarter === 'current') {
            return [
                $now->copy()->firstOfQuarter()->startOfDay(),
                $now->copy()->lastOfQuarter()->endOfDay(),
            ];
        }

        if ($quarter === 'previous') {
            $previous = $now->copy()->subQuarter();

            return [
                $previous->copy()->firstOfQuarter()->startOfDay(),
                $previous->copy()->lastOfQuarter()->endOfDay(),
            ];
        }

        $year = $now->year;

        return match ($quarter) {
            'q1' => [now()->setDate($year, 1, 1)->startOfDay(), now()->setDate($year, 3, 31)->endOfDay()],
            'q2' => [now()->setDate($year, 4, 1)->startOfDay(), now()->setDate($year, 6, 30)->endOfDay()],
            'q3' => [now()->setDate($year, 7, 1)->startOfDay(), now()->setDate($year, 9, 30)->endOfDay()],
            'q4' => [now()->setDate($year, 10, 1)->startOfDay(), now()->setDate($year, 12, 31)->endOfDay()],
            default => [null, null],
        };
    }
}
