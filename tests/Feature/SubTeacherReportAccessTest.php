<?php

namespace Tests\Feature;

use App\Models\ClaimItem;
use App\Models\Student;
use App\Models\StudentClaim;
use App\Models\User;
use Tests\TestCase;

class SubTeacherReportAccessTest extends TestCase
{
    private function subTeacher(User $teacher, string $level = 'collaborator', array $custom = []): User
    {
        $sub = $this->createAccount(['role' => 'sub_teacher']);
        $sub->forceFill([
            'assigned_teacher_id' => $teacher->id,
            'sub_teacher_access_level' => $level,
            'sub_teacher_permissions' => $custom,
        ])->save();

        return $sub;
    }

    public function test_assigned_sub_teacher_can_open_student_report(): void
    {
        $teacher = $this->createTeacher();
        $sub = $this->subTeacher($teacher, 'view_only');

        $this->actingAs($sub)->get(route('teacher.student-report'))->assertOk();
    }

    public function test_assigned_sub_teacher_can_open_admin_activities(): void
    {
        $teacher = $this->createTeacher();
        $sub = $this->subTeacher($teacher, 'view_only');

        $this->actingAs($sub)->get(route('teacher.admin-activities'))->assertOk();
    }

    public function test_sub_teacher_without_report_permission_is_forbidden(): void
    {
        $teacher = $this->createTeacher();
        $sub = $this->subTeacher($teacher, 'custom', ['view_students']);

        $this->actingAs($sub)->get(route('teacher.student-report'))->assertForbidden();
        $this->actingAs($sub)->get(route('teacher.admin-activities'))->assertForbidden();
    }

    public function test_unassigned_sub_teacher_is_forbidden_not_a_server_error(): void
    {
        $sub = $this->createAccount(['role' => 'sub_teacher']);

        $this->actingAs($sub)->get(route('teacher.student-report'))->assertForbidden();
        $this->actingAs($sub)->get(route('teacher.admin-activities'))->assertForbidden();
    }

    public function test_sub_teacher_report_data_is_scoped_to_assigned_teacher(): void
    {
        $teacher = $this->createTeacher();
        $other = $this->createTeacher();
        $sub = $this->subTeacher($teacher, 'view_only');

        $mine = $this->createStudent($teacher, [
            'first_name' => 'ScopedMine', 'lrn' => '108663060201',
        ]);
        $theirs = $this->createStudent($other, [
            'first_name' => 'ScopedTheirs', 'lrn' => '108663060202',
        ]);

        $item = ClaimItem::create([
            'item_name' => 'Notebook', 'points_required' => 10,
            'quantity' => 10, 'status' => 'Available',
        ]);
        foreach ([$mine, $theirs] as $student) {
            StudentClaim::create([
                'student_id' => $student->id, 'claim_item_id' => $item->id,
                'quantity' => 1, 'item_name' => 'Notebook', 'points_deducted' => 10,
                'points_before' => 100, 'points_after' => 90,
                'claim_date' => now()->toDateString(), 'status' => 'Approved',
            ]);
        }

        $this->actingAs($sub)->get(route('teacher.student-report'))
            ->assertOk()->assertSee('ScopedMine')->assertDontSee('ScopedTheirs');

        $this->actingAs($sub)->get(route('teacher.admin-activities'))
            ->assertOk()->assertSee('ScopedMine')->assertDontSee('ScopedTheirs');
    }

    public function test_teacher_admin_and_super_admin_reports_still_load(): void
    {
        $teacher = $this->createTeacher();
        $admin = $this->createAdmin();
        $principal = $this->createSuperAdmin();

        foreach ([$teacher, $admin, $principal] as $user) {
            $prefix = $user->isTeacher() ? 'teacher' : 'admin';
            $this->actingAs($user)->get(route($prefix . '.student-report'))->assertOk();
            $this->actingAs($user)->get(route($prefix . '.admin-activities'))->assertOk();
        }
    }
}
