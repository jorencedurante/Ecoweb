<?php

namespace Tests\Feature;

use App\Models\Student;
use Tests\TestCase;

class UnassignedAccessAndAssignmentUiTest extends TestCase
{
    private function unassignedSubTeacher(string $username = 'unassigned.sub'): \App\Models\User
    {
        return $this->createAccount([
            'role' => 'sub_teacher',
            'username' => $username,
            'name' => 'Unassigned Sub Teacher',
            'status' => 'active',
        ]);
    }

    public function test_unassigned_sub_teacher_sees_styled_notice_on_four_modules_without_data(): void
    {
        $subTeacher = $this->unassignedSubTeacher();
        $teacher = $this->createTeacher();
        $this->createStudent($teacher, ['full_name' => 'Secret Student Record']);

        // The dashboard notice itself already exists; this suite covers the
        // module pages (dashboard SQL uses MySQL-only DAYNAME()).
        $modules = [
            'teacher.students',
            'teacher.bottle-collection',
            'teacher.reports',
            'teacher.qrcode',
            'teacher.certificate',
        ];

        foreach ($modules as $route) {
            $response = $this->actingAs($subTeacher)->get(route($route));
            $response->assertOk()
                ->assertSee('No Teacher Assignment Yet')
                ->assertSee('You have not been assigned to a teacher. Please contact your administrator.', false)
                ->assertDontSee('Secret Student Record');
        }
    }

    public function test_data_endpoints_and_writes_remain_forbidden_for_unassigned_sub_teacher(): void
    {
        $subTeacher = $this->unassignedSubTeacher();
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, ['full_name' => 'Hidden Student']);

        $this->actingAs($subTeacher)->getJson(route('teacher.students.search', ['q' => 'Hidden']))->assertForbidden();
        $this->actingAs($subTeacher)->get(route('teacher.students.info', $student))->assertForbidden();
        $this->actingAs($subTeacher)->getJson(route('teacher.bottle-collection.fetch'))->assertForbidden();
        $this->actingAs($subTeacher)->getJson(route('teacher.qrcode.data'))->assertForbidden();
        $this->actingAs($subTeacher)->get(route('teacher.student-report'))->assertForbidden();

        $this->actingAs($subTeacher)->postJson(route('teacher.bottle-collection.store'), [
            'student_id' => $student->id, 'bottles' => 1, 'collection_date' => now()->toDateString(),
        ])->assertForbidden();
        $this->actingAs($subTeacher)->postJson(route('teacher.qrcode.generate'), [
            'student_id' => $student->id,
        ])->assertForbidden();

        // Certificate data and output remain protected for unassigned Sub Teachers.
        $award = \App\Models\CertificateAward::create([
            'student_id' => $student->id,
            'award_title' => 'Restricted Certificate',
            'awarded_date' => now()->toDateString(),
            'status' => 'Active',
            'issued_by' => $teacher->id,
        ]);
        $this->actingAs($subTeacher)->get(route('teacher.certificate.print', $award))->assertForbidden();
        $this->actingAs($subTeacher)->get(route('teacher.certificate.file', $award))->assertForbidden();
        $this->actingAs($subTeacher)->get(route('teacher.certificate.download', $award))->assertForbidden();
        $this->actingAs($subTeacher)->postJson(route('teacher.certificate.award'), [])->assertForbidden();
    }

    public function test_sub_teacher_login_redirects_to_teacher_dashboard_even_after_principal_session(): void
    {
        $principal = $this->createSuperAdmin(['username' => 'principal-switch-test']);
        $this->post(route('login.submit'), [
            'login' => $principal->username, 'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('logout'))->assertRedirect(route('login'));

        $subTeacher = $this->createAccount([
            'role' => 'sub_teacher', 'username' => 'sub-switch-test', 'status' => 'active',
        ]);
        $this->post(route('login.submit'), [
            'login' => $subTeacher->username, 'password' => 'secret-password',
        ])->assertRedirect(route('teacher.dashboard'));
        $this->assertAuthenticatedAs($subTeacher);
    }

    public function test_stale_admin_intended_url_is_discarded_for_sub_teacher_but_valid_intent_is_kept(): void
    {
        $subTeacher = $this->createAccount([
            'role' => 'sub_teacher', 'username' => 'sub-intent-test', 'status' => 'active',
        ]);

        $this->withSession(['url.intended' => route('admin.dashboard')])
            ->post(route('login.submit'), [
                'login' => $subTeacher->username, 'password' => 'secret-password',
            ])->assertRedirect(route('teacher.dashboard'));

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->withSession(['url.intended' => route('teacher.certificate')])
            ->post(route('login.submit'), [
                'login' => $subTeacher->username, 'password' => 'secret-password',
            ])->assertRedirect(route('teacher.certificate'));
    }

    public function test_principal_login_after_sub_teacher_logout_uses_admin_dashboard(): void
    {
        $subTeacher = $this->unassignedSubTeacher('sub-then-principal');
        $this->post(route('login.submit'), [
            'login' => $subTeacher->username, 'password' => 'secret-password',
        ])->assertRedirect(route('teacher.dashboard'));
        $this->post(route('logout'))->assertRedirect(route('login'));

        $principal = $this->createSuperAdmin(['username' => 'principal-after-sub']);
        $this->post(route('login.submit'), [
            'login' => $principal->username, 'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_assigned_sub_teacher_still_sees_only_authorized_records(): void
    {
        $teacher = $this->createTeacher();
        $subTeacher = $this->createAccount([
            'role' => 'sub_teacher', 'username' => 'assigned.sub', 'status' => 'active',
            'assigned_teacher_id' => $teacher->id, 'sub_teacher_access_level' => 'collaborator',
        ]);
        $student = $this->createStudent($teacher, [
            'first_name' => 'Assigned', 'last_name' => 'ClassStudent',
            'full_name' => 'Assigned ClassStudent',
        ]);

        $this->actingAs($subTeacher)->get(route('teacher.students'))
            ->assertOk()->assertDontSee('No Teacher Assignment Yet')
            ->assertSee($student->first_name);
    }

    public function test_assignment_actions_are_icon_buttons_with_tooltips_and_centered(): void
    {
        $principal = $this->createSuperAdmin();
        $teacher = $this->createTeacher();
        $subTeacher = $this->createAccount([
            'role' => 'sub_teacher', 'name' => 'Icon Sub Teacher', 'status' => 'active',
            'assigned_teacher_id' => $teacher->id, 'sub_teacher_access_level' => 'collaborator',
        ]);

        $html = (string) $this->actingAs($principal)->get(route('admin.sub-teacher-assignments'))->getContent();

        foreach ([
            'title="View Assignment" aria-label="View Assignment"',
            'title="Edit Assignment" aria-label="Edit Assignment"',
            'title="Unassign Sub Teacher" aria-label="Unassign Sub Teacher"',
            'class="sta-icon-btn sta-icon-view sta-view"',
            'class="sta-icon-btn sta-icon-edit sta-edit"',
            'class="sta-icon-btn sta-icon-unassign sta-unassign"',
            'class="sta-actions-header"',
            '>View</button>',
        ] as $index => $needle) {
            if ($index === 7) {
                $this->assertStringNotContainsString($needle, $html);
            } else {
                $this->assertStringContainsString($needle, $html);
            }
        }

        $pageSource = (string) file_get_contents(resource_path('views/pages/sub-teacher-assignments.blade.php'));
        $this->assertStringContainsString('.sta-actions{display:flex;justify-content:center;align-items:center;gap:8px;flex-wrap:nowrap;}', $pageSource);
        $this->assertStringContainsString('.sta-icon-btn:focus-visible', $pageSource);
        $this->assertStringContainsString('.sta-table-card .table-responsive{overflow-x:auto;}', $pageSource);

        // Existing AJAX endpoint still returns the same action classes.
        $data = $this->actingAs($principal)->getJson(route('admin.sub-teacher-assignments.data'));
        $data->assertOk()->assertSee('Icon Sub Teacher')->assertSee('sta-icon-unassign');
    }
}
