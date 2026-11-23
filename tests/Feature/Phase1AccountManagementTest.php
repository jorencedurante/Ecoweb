<?php

namespace Tests\Feature;

use App\Mail\TeacherSetupInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class Phase1AccountManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_email_change_cancel_uses_custom_modal_and_preserves_otp_inputs(): void
    {
        $teacher = $this->createTeacher([
            'pending_email' => 'new-address@example.com',
            'email_change_otp' => Hash::make('123456'),
            'email_change_otp_expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($teacher)->get(route('account.email-change.verify'));

        $response->assertOk();
        $response->assertSee('Cancel Email Change?');
        $response->assertSee('Are you sure you want to cancel your email change? Your current email address will remain unchanged.', false);
        $response->assertSee('Continue Verification');
        $response->assertSee('Yes, Cancel Change');
        $response->assertSee('id="otp_1"', false);
        $response->assertDontSee("confirm('Cancel email change?')", false);
    }

    public function test_email_change_cancel_endpoint_still_clears_pending_change(): void
    {
        $teacher = $this->createTeacher([
            'pending_email' => 'new-address@example.com',
            'email_change_otp' => Hash::make('123456'),
            'email_change_otp_expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($teacher)->post(route('account.email-change.cancel'));

        $response->assertRedirect(route(portal_prefix() . '.settings.edit'));
        $response->assertSessionHas('success', 'Email change request cancelled.');

        $teacher->refresh();
        $this->assertNull($teacher->pending_email);
        $this->assertNull($teacher->email_change_otp);
        $this->assertNull($teacher->email_change_otp_expires_at);
    }

    public function test_create_account_modal_has_four_roles_and_red_asterisks(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route(portal_prefix() . '.teachers'));

        $response->assertOk();
        $response->assertSee('>Create Account<', false);
        $response->assertSee('Add a new staff account and send an invitation to their email.', false);
        $response->assertSee('<option value="teacher" selected>Teacher</option>', false);
        $response->assertSee('<option value="sub_teacher">Sub Teacher</option>', false);
        $response->assertSee('<option value="admin">Admin</option>', false);
        $response->assertSee('<option value="super_admin">Principal</option>', false);
        $response->assertDontSee('Create Teacher Account');
        $response->assertSee('.create-teacher-field label span{color:#dc2626;}', false);
    }

    public function test_admin_can_invite_sub_teacher_and_super_admin_can_invite_principal(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->postJson(route('admin.teachers.create-account'), [
            'name' => 'Sub Teacher One',
            'username' => 'sub.teacher1',
            'email' => 'sub-teacher1@example.com',
            'position' => 'Sub Teacher',
            'role' => 'sub_teacher',
        ])->assertOk()->assertJsonPath('success', true)->assertJsonPath('account.role', 'sub_teacher');

        $subTeacher = User::where('email', 'sub-teacher1@example.com')->firstOrFail();
        $this->assertSame('sub_teacher', $subTeacher->role);
        $this->assertSame('pending_setup', $subTeacher->status);
        $this->assertNotNull($subTeacher->email_verification_code);
        Mail::assertSent(TeacherSetupInvitation::class, function ($mail) use ($subTeacher) {
            return $mail->hasTo($subTeacher->email)
                && str_contains($mail->setupUrl, 'teacher-setup/' . $subTeacher->id);
        });

        $superAdmin = $this->createSuperAdmin();
        $this->actingAs($superAdmin)->postJson(route('admin.teachers.create-account'), [
            'name' => 'Principal Two',
            'username' => 'principal2',
            'email' => 'principal2@example.com',
            'position' => 'Principal',
            'role' => 'super_admin',
        ])->assertOk()->assertJsonPath('account.role', 'super_admin');

        $principal = User::where('email', 'principal2@example.com')->firstOrFail();
        $this->assertSame('super_admin', $principal->role);
        $this->assertSame('pending_setup', $principal->status);
    }

    public function test_privileged_role_creation_is_blocked_server_side(): void
    {
        $admin = $this->createAdmin();

        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs($admin)->postJson(route('admin.teachers.create-account'), [
                'name' => 'Escalation Attempt',
                'username' => 'escalate.' . $role,
                'email' => 'escalate.' . $role . '@example.com',
                'position' => 'Staff',
                'role' => $role,
            ])->assertForbidden();
        }

        $this->assertDatabaseMissing('users', ['email' => 'escalate.admin@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'escalate.super_admin@example.com']);

        $teacher = $this->createTeacher();
        $this->actingAs($teacher)->postJson(route('admin.teachers.create-account'), [
            'name' => 'Teacher Attempt',
            'username' => 'teacher.attempt',
            'email' => 'teacher-attempt@example.com',
            'position' => 'Teacher',
            'role' => 'teacher',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'teacher-attempt@example.com']);
    }

    public function test_role_labels_display_principal_and_sub_teacher(): void
    {
        $this->assertSame('Principal', User::roleLabel('super_admin'));
        $this->assertSame('Sub Teacher', User::roleLabel('sub_teacher'));
        $this->assertSame('Teacher', User::roleLabel('teacher'));
        $this->assertSame('Admin', User::roleLabel('admin'));
    }

    public function test_sub_teacher_has_no_student_record_access_in_phase_one(): void
    {
        $subTeacher = $this->createAccount([
            'name' => 'Limited Sub Teacher',
            'role' => 'sub_teacher',
            'status' => 'active',
        ]);
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $this->actingAs($subTeacher)->get(route('admin.students'))->assertForbidden();
        $this->actingAs($subTeacher)->get(route('teacher.students'))
            ->assertOk()->assertSee('No Teacher Assignment Yet');
        $this->actingAs($subTeacher)->get(route('admin.students.info', $student))->assertForbidden();
    }

    public function test_archived_accounts_modal_has_delete_action_and_confirmation(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route(portal_prefix() . '.teachers'));

        $response->assertOk();
        $response->assertSee('Permanently Delete Account?');
        $response->assertSee('Are you sure you want to permanently delete this archived account? This action cannot be undone.', false);
        $response->assertSee('Delete Account');
        $response->assertSee('action-btn-delete delete-archived', false);
        $response->assertSee("TEACHERS_BASE_URL+'/'+id+'/delete'", false);
    }

    public function test_safe_archived_account_can_be_permanently_deleted(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $archived = $this->createTeacher([
            'name' => 'Delete Me',
            'status' => 'archived',
        ]);

        $response = $this->actingAs($superAdmin)
            ->deleteJson(route('admin.teachers.delete', $archived->id));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('users', ['id' => $archived->id]);
        $this->assertDatabaseHas('admin_activities', [
            'user_id' => $superAdmin->id,
            'action' => 'Deleted Archived Account',
        ]);
    }

    public function test_account_with_student_records_cannot_be_deleted(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['status' => 'archived']);
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($superAdmin)
            ->deleteJson(route('admin.teachers.delete', $teacher->id));

        $response->assertStatus(422);
        $response->assertJsonPath('success', null);
        $this->assertStringContainsString('student record(s)', $response->json('error'));
        $this->assertDatabaseHas('users', ['id' => $teacher->id]);
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_only_archived_accounts_can_be_deleted(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $activeTeacher = $this->createTeacher();

        $response = $this->actingAs($superAdmin)
            ->deleteJson(route('admin.teachers.delete', $activeTeacher->id));

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Only archived accounts can be permanently deleted.');
        $this->assertDatabaseHas('users', ['id' => $activeTeacher->id]);
    }

    public function test_non_super_admin_cannot_delete_archived_accounts(): void
    {
        $admin = $this->createAdmin();
        $archived = $this->createTeacher(['status' => 'archived']);

        $this->actingAs($admin)
            ->deleteJson(route('admin.teachers.delete', $archived->id))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $archived->id]);
    }

    public function test_teacher_dashboard_no_longer_displays_available_claim_items(): void
    {
        $dashboard = file_get_contents(resource_path('views/pages/dashboard.blade.php'));

        $this->assertStringNotContainsString('Available Claim Items', $dashboard);
        $this->assertStringNotContainsString('availableClaimItems', $dashboard);
        $this->assertStringContainsString('Total Students', $dashboard);
        $this->assertStringContainsString('Total Bottles Collected', $dashboard);
        $this->assertStringContainsString('Certificates', $dashboard);
        $this->assertStringContainsString('Total Claimed Items', $dashboard);
        $this->assertStringContainsString('Total Points Redeemed', $dashboard);
    }
}
