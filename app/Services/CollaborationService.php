<?php

namespace App\Services;

use App\Models\User;

/**
 * Central authority for Sub Teacher assignments and delegated permissions.
 *
 * A Sub Teacher never owns student records. Access is resolved to the single
 * assigned Teacher, whose existing enrollments remain the source of truth.
 * Every check here is server-side; hidden UI elements are never relied upon.
 */
class CollaborationService
{
    // Internal permission keys. "view_certificates" is always granted with
    // viewing access; the eight configurable ones are exposed in the UI.
    public const VIEW_STUDENTS = 'view_students';
    public const ADD_STUDENTS = 'add_students';
    public const EDIT_STUDENTS = 'edit_students';
    public const RECORD_BOTTLE_COLLECTION = 'record_bottle_collection';
    public const VIEW_COLLECTION_HISTORY = 'view_collection_history';
    public const VIEW_CERTIFICATES = 'view_certificates';
    public const MANAGE_CERTIFICATES = 'manage_certificates';
    public const VIEW_REPORTS = 'view_reports';
    public const PRINT_REPORTS = 'print_reports';

    public const ACCESS_COLLABORATOR = 'collaborator';
    public const ACCESS_VIEW_ONLY = 'view_only';
    public const ACCESS_CUSTOM = 'custom';

    public const UNASSIGNED_MESSAGE = 'You have not been assigned to a teacher. Please contact your administrator.';

    /** @return array<string,string> */
    public static function accessLevels(): array
    {
        return [
            self::ACCESS_COLLABORATOR => 'Collaborator',
            self::ACCESS_VIEW_ONLY => 'View Only',
            self::ACCESS_CUSTOM => 'Custom',
        ];
    }

    /** @return array<string,string> */
    public static function configurablePermissions(): array
    {
        return [
            self::VIEW_STUDENTS => 'View Students',
            self::ADD_STUDENTS => 'Add Students',
            self::EDIT_STUDENTS => 'Edit Students',
            self::RECORD_BOTTLE_COLLECTION => 'Record Bottle Collection',
            self::VIEW_COLLECTION_HISTORY => 'View Collection History',
            self::MANAGE_CERTIFICATES => 'Manage Certificates',
            self::VIEW_REPORTS => 'View Reports',
            self::PRINT_REPORTS => 'Print Reports',
        ];
    }

    /** @return array<int,string> */
    public static function permissionsFor(string $accessLevel, $customPermissions = null): array
    {
        switch ($accessLevel) {
            case self::ACCESS_VIEW_ONLY:
                return [
                    self::VIEW_STUDENTS,
                    self::VIEW_COLLECTION_HISTORY,
                    self::VIEW_CERTIFICATES,
                    self::VIEW_REPORTS,
                ];

            case self::ACCESS_CUSTOM:
                $selected = is_array($customPermissions) ? $customPermissions : [];
                $permissions = array_values(array_intersect(
                    array_keys(self::configurablePermissions()),
                    $selected
                ));
                // Certificate viewing is implicit whenever a student can be viewed.
                if (in_array(self::VIEW_STUDENTS, $permissions, true)) {
                    $permissions[] = self::VIEW_CERTIFICATES;
                }
                return array_values(array_unique($permissions));

            case self::ACCESS_COLLABORATOR:
            default:
                return array_merge(array_keys(self::configurablePermissions()), [self::VIEW_CERTIFICATES]);
        }
    }

    /**
     * Assignments live on the users row itself (assigned_teacher_id).
     */
    public function isAssigned(?User $user): bool
    {
        return $user !== null && $user->hasEffectiveAssignment();
    }

    /**
     * The Teacher whose existing records this user may access.
     * Null for admins (global) and for Sub Teachers without an effective assignment.
     */
    public function scopeTeacherId(?User $user): ?int
    {
        if (!$user) {
            return null;
        }

        if ($user->isTeacher()) {
            return (int) $user->id;
        }

        return $user->scopeTeacherId();
    }

    public function can(?User $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        // Admins keep their existing global authority.
        if ($user->isAdminLevel()) {
            return true;
        }

        // Teachers act on their own records under the pre-existing business rules.
        if ($user->isTeacher()) {
            return true;
        }

        $assignment = $user->assignmentData();
        if (!$assignment) {
            return false;
        }

        return in_array(
            $permission,
            self::permissionsFor($assignment['access_level'], $assignment['permissions']),
            true
        );
    }

    public function unassignedMessage(): string
    {
        return self::UNASSIGNED_MESSAGE;
    }

    /**
     * Resolve the delegated permission required by a teacher-portal route.
     * Unknown routes are denied for Sub Teachers (fail closed).
     */
    public function permissionForRoute(string $suffix, string $method): ?string
    {
        $get = strtoupper($method) === 'GET';

        $viewRoutes = [
            'students', 'students.search', 'students.filter', 'students.info',
            'students.overview', 'students.achievements', 'students.awards',
            'students.historical', 'students.archived', 'students.school-year-archives',
            'qrcode', 'qrcode.data', 'qrcode.searchStudents',
        ];
        if (in_array($suffix, $viewRoutes, true)) {
            return self::VIEW_STUDENTS;
        }

        $addRoutes = ['students.store', 'students.import', 'students.import.template'];
        if (in_array($suffix, $addRoutes, true)) {
            return self::ADD_STUDENTS;
        }

        $editRoutes = [
            'students.update', 'students.archive', 'students.archive-all',
            'students.restore', 'students.unarchive', 'students.unarchive-all',
            'students.re-enroll', 'students.move-to-school-year',
            'students.fix-school-years', 'students.achievements.progress.update',
            'qrcode.generate', 'qrcode.download', 'qrcode.print',
        ];
        if (in_array($suffix, $editRoutes, true)) {
            return self::EDIT_STUDENTS;
        }

        if (in_array($suffix, ['bottle-collection.fetch'], true)
            || ($suffix === 'bottle-collection' && $get)) {
            return self::VIEW_COLLECTION_HISTORY;
        }
        if ($suffix === 'bottle-collection.store') {
            return self::RECORD_BOTTLE_COLLECTION;
        }

        if ($get && in_array($suffix, [
            'certificate', 'certificate.create', 'certificate.edit',
            'certificate.templates.list', 'certificate.print',
            'certificate.download', 'certificate.file',
        ], true)) {
            return self::VIEW_CERTIFICATES;
        }
        if (in_array($suffix, [
            'certificate.store', 'certificate.template.store', 'certificate.save-canvas',
            'certificate.award', 'certificate.update-canvas', 'certificate.archive',
            'certificate.destroy',
        ], true)) {
            return self::MANAGE_CERTIFICATES;
        }

        $reportRoutes = [
            'reports', 'student-report', 'student-report.details', 'bottle-report',
            'bottle-report.search', 'admin-activities',
            'admin-activities.teacher-students', 'admin-activities.student-claims',
        ];
        if (in_array($suffix, $reportRoutes, true)) {
            return self::VIEW_REPORTS;
        }
        if (str_starts_with($suffix, 'reports.') || $suffix === 'reports.student.print') {
            return self::PRINT_REPORTS;
        }

        // achievement-quests.* and any unknown route are not delegated.
        return null;
    }
}
