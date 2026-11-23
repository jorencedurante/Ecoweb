<?php

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class Phase3UIFixesTest extends TestCase
{
    private function subTeacher(string $name): User
    {
        return $this->createAccount(['role' => 'sub_teacher', 'name' => $name]);
    }

    public function test_assignment_live_search_endpoint_searches_and_filters(): void
    {
        $principal = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['name' => 'Kathleen Tabadero']);
        $maria = $this->subTeacher('Maria Santos');
        $john = $this->subTeacher('John Reyes');
        $diana = $this->subTeacher('Diana Lopez');

        $maria->forceFill([
            'assigned_teacher_id' => $teacher->id,
            'sub_teacher_access_level' => 'collaborator',
            'sub_teacher_permissions' => [],
        ])->save();

        $response = $this->actingAs($principal)->getJson(route('admin.sub-teacher-assignments.data', [
            'search' => 'santos',
        ]));

        $response->assertOk()->assertJsonPath('success', true);
        $response->assertSee('Maria Santos');
        $response->assertDontSee('John Reyes');
        $response->assertSee('Kathleen Tabadero');

        $unassigned = $this->actingAs($principal)->getJson(route('admin.sub-teacher-assignments.data', [
            'status' => 'unassigned',
        ]));
        $unassigned->assertOk();
        $unassigned->assertSee('Diana Lopez');
        $unassigned->assertDontSee('Maria Santos');
    }

    public function test_assignment_live_search_pagination_is_limited_to_ten(): void
    {
        $principal = $this->createSuperAdmin();
        for ($i = 1; $i <= 12; $i++) {
            $this->subTeacher('Sub Teacher ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        $response = $this->actingAs($principal)->getJson(route('admin.sub-teacher-assignments.data'));
        $response->assertOk();

        $this->assertSame(12, $response->json('pagination.total'));
        $this->assertSame(2, $response->json('pagination.last_page'));
        $this->assertSame(10, substr_count($response->json('html'), 'class="btn btn-sm'));
        $response->assertSee('sta-page');
    }

    public function test_system_settings_is_viewable_by_all_roles_but_writable_by_principal_only(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $subTeacher = $this->createAccount(['role' => 'sub_teacher', 'status' => 'active']);
        $principal = $this->createSuperAdmin();

        foreach ([$admin, $teacher, $subTeacher] as $viewer) {
            $this->actingAs($viewer)->get(route('admin.system-settings'))
                ->assertOk()
                ->assertDontSee('View only. Only Principal can edit these settings.')
                ->assertDontSee('settings-view-note')
                ->assertDontSee('id="settingsSaveBtn"', false)
                ->assertSee('readonly');
        }

        foreach ([$admin, $teacher, $subTeacher] as $viewer) {
            $this->actingAs($viewer)->post(route('admin.settings.general'), [
                'admin_name' => 'Hacked',
                'school_organization' => 'Hacked',
            ])->assertForbidden();
            $this->actingAs($viewer)->post(route('admin.settings.security'), [])->assertForbidden();
        }

        $this->actingAs($principal)->get(route('admin.system-settings'))
            ->assertOk()
            ->assertSee('id="settingsSaveBtn"', false)
            ->assertDontSee('View only. Only Principal can edit these settings.');
    }

    public function test_system_settings_link_visible_in_sidebar_for_all_roles(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $principal = $this->createSuperAdmin();

        foreach ([$admin, $teacher, $principal] as $user) {
            $this->actingAs($user)->get(route('admin.system-settings'))
                ->assertOk()->assertSee('data-label="System Settings"', false);
        }
    }

    public function test_archived_action_buttons_have_spacing(): void
    {
        $source = (string) file_get_contents(resource_path('views/pages/teachers.blade.php'));

        $this->assertStringContainsString('gap:10px', $source);
        $this->assertStringContainsString('action-btn-restore unarchive', $source);
        $this->assertStringContainsString('action-btn-delete delete-archived', $source);
        $this->assertStringContainsString('flex-direction:row;align-items:center;justify-content:center;gap:10px;flex-wrap:nowrap', $source);
        $this->assertStringContainsString('width:132px;min-width:132px', $source);
    }

    public function test_qr_live_search_endpoint_searches_by_name_lrn_and_qr_value(): void
    {
        $teacher = $this->createTeacher();
        $studentA = $this->createStudent($teacher, ['first_name' => 'Alpha', 'last_name' => 'Learner', 'lrn' => '80000000001']);
        $studentB = $this->createStudent($teacher, ['first_name' => 'Beta', 'last_name' => 'Learner', 'lrn' => '80000000002']);

        QrCode::create([
            'student_id' => $studentA->id,
            'student_name' => $studentA->full_name,
            'qr_type' => 'lrn',
            'qr_value' => '80000000001',
            'created_by' => $teacher->id,
        ]);
        QrCode::create([
            'student_id' => $studentB->id,
            'student_name' => $studentB->full_name,
            'qr_type' => 'lrn',
            'qr_value' => '80000000002',
            'created_by' => $teacher->id,
        ]);

        $byName = $this->actingAs($teacher)->getJson(route('teacher.qrcode.data', ['search' => 'Alpha']));
        $byName->assertOk()->assertJsonPath('success', true);
        $byName->assertSee('Alpha Learner');
        $byName->assertDontSee('Beta Learner');

        $byLrn = $this->actingAs($teacher)->getJson(route('teacher.qrcode.data', ['search' => '80000000002']));
        $byLrn->assertOk()->assertSee('Beta Learner');
        $byLrn->assertDontSee('Alpha Learner');
    }

    public function test_qr_live_search_date_filter_and_pagination(): void
    {
        $teacher = $this->createTeacher();

        for ($i = 1; $i <= 12; $i++) {
            $student = $this->createStudent($teacher);
            QrCode::create([
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'qr_type' => 'lrn',
                'qr_value' => 'QR-' . $student->lrn,
                'created_by' => $teacher->id,
            ]);
        }

        $response = $this->actingAs($teacher)->getJson(route('teacher.qrcode.data'));
        $response->assertOk();
        $this->assertSame(12, $response->json('pagination.total'));
        $this->assertSame(2, $response->json('pagination.last_page'));
        $response->assertSee('qr-page');

        $today = now()->toDateString();
        $filtered = $this->actingAs($teacher)->getJson(route('teacher.qrcode.data', ['date' => $today]));
        $filtered->assertOk();
        $this->assertSame(12, $filtered->json('pagination.total'));

        $none = $this->actingAs($teacher)->getJson(route('teacher.qrcode.data', ['date' => '2000-01-01']));
        $none->assertOk();
        $this->assertSame(0, $none->json('pagination.total'));
    }

    public function test_dashboard_uses_users_relationships_without_missing_table(): void
    {
        $teacher = $this->createTeacher();
        $subTeacher = $this->subTeacher('Dashboard Sub');
        $unassigned = $this->subTeacher('Unassigned Sub');

        $subTeacher->forceFill([
            'assigned_teacher_id' => $teacher->id,
            'sub_teacher_access_level' => 'collaborator',
        ])->save();

        $this->assertSame(1, $teacher->subTeachers()->count());
        $this->assertSame($teacher->id, $subTeacher->fresh()->scopeTeacherId());
        $this->assertNull($unassigned->fresh()->scopeTeacherId());
        $this->assertSame('Unassigned', $unassigned->fresh()->assignmentStatus());

        $files = [
            'app/Http/Controllers/DashboardController.php',
            'app/Models/User.php',
            'app/Services/CollaborationService.php',
            'app/Http/Controllers/SubTeacherAssignmentController.php',
            'app/Http/Middleware/SubTeacherAccess.php',
            'routes/web.php',
            'resources/views/pages/dashboard.blade.php',
        ];

        foreach ($files as $file) {
            $this->assertStringNotContainsString(
                'sub_teacher_assignments',
                (string) file_get_contents(base_path($file)),
                $file . ' must not query the removed table'
            );
        }
    }
}
