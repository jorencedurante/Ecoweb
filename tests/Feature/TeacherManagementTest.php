<?php

namespace Tests\Feature;

use App\Mail\SendOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_accounts_page_lists_users_and_ignores_legacy_teachers_table(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['name' => 'Visible Teacher']);

        DB::table('teachers')->insert([
            'admin_id' => 'ADM999',
            'name' => 'Legacy Ghost Teacher',
            'email' => 'ghost@legacy.test',
            'position' => 'Teacher',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.teachers'));

        $response->assertOk();
        $response->assertSee('Visible Teacher');
        $response->assertSee($teacher->email);
        $response->assertDontSee('Legacy Ghost Teacher');
    }

    public function test_super_admin_can_add_teacher_account_without_creating_teachers_row(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->post(route('admin.teachers.store'), [
            'name' => 'New Teacher',
            'username' => 'newteacher1',
            'email' => 'newteacher1@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'teacher',
            'position' => 'Teacher 1',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.teachers'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'newteacher1@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('teacher', $user->role);
        $this->assertSame('newteacher1', $user->username);
        $this->assertNull($user->email_verified_at);

        $this->assertDatabaseMissing('teachers', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('teachers', ['email' => 'newteacher1@example.com']);

        Mail::assertSent(SendOtpMail::class);
    }

    public function test_adding_teacher_with_duplicate_username_is_rejected(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->createTeacher(['username' => 'takenuser']);

        $response = $this->actingAs($superAdmin)->post(route('admin.teachers.store'), [
            'name' => 'Another Teacher',
            'username' => 'takenuser',
            'email' => 'another@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertDatabaseMissing('users', ['username' => 'takenuser', 'email' => 'another@example.com']);
    }

    public function test_admin_account_cannot_create_accounts(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.teachers.store'), [
            'name' => 'Nope Teacher',
            'username' => 'nope1',
            'email' => 'nope1@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'nope1@example.com']);
    }

    public function test_teacher_cannot_access_account_management(): void
    {
        $teacher = $this->createTeacher();

        $this->actingAs($teacher)->get(route('admin.teachers'))->assertForbidden();
        $this->actingAs($teacher)->post(route('admin.teachers.store'), [])->assertForbidden();
    }

    public function test_super_admin_can_edit_teacher_account(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['username' => 'edittarget', 'email' => 'edittarget@example.com']);

        $response = $this->actingAs($superAdmin)->put(route('admin.teachers.update', $teacher->id), [
            'name' => 'Edited Teacher',
            'username' => 'edittarget',
            'email' => 'edittarget@example.com',
            'role' => 'teacher',
            'position' => 'Teacher 5',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.teachers'));

        $teacher->refresh();
        $this->assertSame('Edited Teacher', $teacher->name);
        $this->assertSame('Teacher 5', $teacher->position);
        $this->assertSame('teacher', $teacher->role);
    }

    public function test_super_admin_can_archive_teacher_account(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher();

        $response = $this->actingAs($superAdmin)->patch(route('admin.teachers.archive', $teacher->id));

        $response->assertOk();
        $this->assertSame('archived', $teacher->refresh()->status);
    }

    public function test_super_admin_can_restrict_teacher_account(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['status' => 'active']);

        $response = $this->actingAs($superAdmin)->patch(route('admin.teachers.restrict', $teacher->id));

        $response->assertOk();
        $this->assertSame('restricted', $teacher->refresh()->status);
    }

    public function test_super_admin_can_unrestrict_teacher_account(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['status' => 'restricted']);

        $response = $this->actingAs($superAdmin)->patch(route('admin.teachers.unrestrict', $teacher->id));

        $response->assertOk();
        $this->assertSame('active', $teacher->refresh()->status);
    }

    public function test_last_super_admin_cannot_be_archived(): void
    {
        $superAdmin = $this->createSuperAdmin();

        // Ensure this account is the only Super Admin in the database,
        // regardless of any pre-existing seeded data.
        User::where('role', 'super_admin')
            ->where('id', '!=', $superAdmin->id)
            ->update(['role' => 'admin']);

        $response = $this->actingAs($superAdmin)->patch(route('admin.teachers.archive', $superAdmin->id));

        $response->assertStatus(422);
        $this->assertSame('active', $superAdmin->refresh()->status);
    }

    public function test_cannot_restrict_own_account(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->patch(route('admin.teachers.restrict', $superAdmin->id));

        $response->assertStatus(422);
        $this->assertSame('active', $superAdmin->refresh()->status);
    }

    public function test_cannot_archive_own_account(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->patch(route('admin.teachers.archive', $superAdmin->id));

        $response->assertStatus(422);
        $this->assertSame('active', $superAdmin->refresh()->status);
    }

    public function test_non_super_admin_cannot_restrict_account(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher(['status' => 'active']);

        $response = $this->actingAs($admin)->patch(route('admin.teachers.restrict', $teacher->id));

        $response->assertForbidden();
        $this->assertSame('active', $teacher->refresh()->status);
    }

    public function test_non_super_admin_cannot_archive_account(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher(['status' => 'active']);

        $response = $this->actingAs($admin)->patch(route('admin.teachers.archive', $teacher->id));

        $response->assertForbidden();
        $this->assertSame('active', $teacher->refresh()->status);
    }

    public function test_teacher_list_supports_search_and_role_filter(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->createTeacher(['name' => 'Searchable Annie', 'email' => 'annie@example.com']);
        $this->createAdmin(['name' => 'Filterable Adam', 'email' => 'adam@example.com']);

        $search = $this->actingAs($superAdmin)->get(route('admin.teachers', ['search' => 'Searchable Annie']));
        $search->assertOk();
        $search->assertSee('Searchable Annie');
        $search->assertDontSee('Filterable Adam');

        $filter = $this->actingAs($superAdmin)->get(route('admin.teachers', ['role' => 'admin']));
        $filter->assertOk();
        $filter->assertSee('Filterable Adam');
        $filter->assertDontSee('Searchable Annie');
    }

    public function test_archived_teacher_cannot_log_in(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher([
            'username' => 'archivedteacher',
            'email' => 'archived@example.com',
            'password' => 'password123',
            'status' => 'archived',
        ]);

        $response = $this->post(route('login.submit'), [
            'login' => 'archivedteacher',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
        $this->assertNull(auth()->user());
    }

    public function test_inactive_teacher_cannot_log_in(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher([
            'username' => 'inactiveteacher',
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'status' => 'inactive',
        ]);

        $response = $this->post(route('login.submit'), [
            'login' => 'inactiveteacher',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
        $this->assertNull(auth()->user());
    }

    public function test_active_teacher_can_still_log_in(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher([
            'username' => 'activeteacher',
            'email' => 'active@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $response = $this->post(route('login.submit'), [
            'login' => 'activeteacher',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertNotNull(auth()->user());
    }

    public function test_archived_user_cannot_access_protected_routes(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher([
            'username' => 'blockedteacher',
            'email' => 'blocked@example.com',
            'password' => 'password123',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($teacher);

        $this->assertTrue($teacher->isActive());

        $teacher->update(['status' => 'archived']);
        $teacher->refresh();

        $this->assertFalse($teacher->isActive());

        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertFalse(auth()->check());
    }

    public function test_archiving_teacher_clears_remember_token(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher([
            'username' => 'tokenteacher',
            'email' => 'token@example.com',
            'status' => 'active',
        ]);

        $teacher->forceFill(['remember_token' => 'some-remember-token'])->save();
        $this->assertSame('some-remember-token', $teacher->refresh()->remember_token);

        $this->actingAs($superAdmin)->patch(route('admin.teachers.archive', $teacher->id));

        $this->assertNull($teacher->refresh()->remember_token);
        $this->assertSame('archived', $teacher->refresh()->status);
    }

    public function test_archiving_teacher_preserves_students(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $this->actingAs($superAdmin)->patch(route('admin.teachers.archive', $teacher->id));

        $teacher->refresh();
        $this->assertSame('archived', $teacher->status);

        $student->refresh();
        $this->assertNotNull($student->id);
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_teacher_fetch_endpoint_returns_filtered_json(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->createTeacher(['name' => 'Alpha Teacher', 'email' => 'alpha@example.com', 'role' => 'teacher']);
        $this->createTeacher(['name' => 'Beta Teacher', 'email' => 'beta@example.com', 'role' => 'admin']);
        $this->createAdmin(['name' => 'Gamma Admin', 'email' => 'gamma@example.com', 'role' => 'admin']);

        $response = $this->actingAs($superAdmin)->get(route('admin.teachers.fetch', ['search' => 'Alpha']));
        $response->assertOk();
        $response->assertJsonStructure(['accounts', 'current_page', 'last_page', 'per_page', 'total', 'from', 'to']);
        $response->assertJsonFragment(['name' => 'Alpha Teacher']);
        $response->assertJsonMissing(['name' => 'Beta Teacher']);

        $response = $this->actingAs($superAdmin)->get(route('admin.teachers.fetch', ['role' => 'admin']));
        $response->assertOk();
        $data = $response->json();
        $names = array_column($data['accounts'], 'name');
        $this->assertContains('Beta Teacher', $names);
        $this->assertContains('Gamma Admin', $names);
        $this->assertNotContains('Alpha Teacher', $names);

        $response = $this->actingAs($superAdmin)->get(route('admin.teachers.fetch', ['status' => 'archived']));
        $response->assertOk();
        $this->assertSame(0, $response->json('total'));
    }

    public function test_teacher_fetch_endpoint_search_single_letter(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->createTeacher(['name' => 'Zara Teacher', 'username' => 'zara_user', 'email' => 'zara@example.com']);
        $this->createTeacher(['name' => 'John Teacher', 'username' => 'john_user', 'email' => 'john@example.com']);

        $response = $this->actingAs($superAdmin)->get(route('admin.teachers.fetch', ['search' => 'z']));
        $response->assertOk();
        $data = $response->json();
        $names = array_column($data['accounts'], 'name');
        $this->assertContains('Zara Teacher', $names);
        $this->assertNotContains('John Teacher', $names);
    }

    public function test_no_matching_accounts_returns_empty(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->createTeacher(['name' => 'Only Teacher']);

        $response = $this->actingAs($superAdmin)->get(route('admin.teachers.fetch', ['search' => 'Nonexistent']));
        $response->assertOk();
        $this->assertSame(0, $response->json('total'));
        $this->assertEmpty($response->json('accounts'));
    }

    public function test_super_admin_can_reactivate_archived_teacher(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher([
            'username' => 'reactivateteacher',
            'email' => 'reactivate@example.com',
            'status' => 'archived',
        ]);

        $this->assertSame('archived', $teacher->refresh()->status);

        $response = $this->actingAs($superAdmin)->patch(route('admin.teachers.reactivate', $teacher->id));

        $response->assertOk();
        $this->assertSame('active', $teacher->refresh()->status);
    }

    public function test_reactivated_teacher_can_log_in_again(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher([
            'username' => 'reloginteacher',
            'email' => 'relogin@example.com',
            'password' => 'password123',
            'status' => 'archived',
        ]);

        $this->actingAs($superAdmin)->patch(route('admin.teachers.reactivate', $teacher->id));
        $this->assertSame('active', $teacher->refresh()->status);

        $response = $this->post(route('login.submit'), [
            'login' => 'reloginteacher',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertNotNull(auth()->user());
    }

    public function test_reactivation_preserves_student_records(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['status' => 'archived']);
        $student = $this->createStudent($teacher);

        $this->actingAs($superAdmin)->patch(route('admin.teachers.reactivate', $teacher->id));

        $teacher->refresh();
        $this->assertSame('active', $teacher->status);

        $student->refresh();
        $this->assertNotNull($student->id);
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_non_super_admin_cannot_reactivate_account(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher(['status' => 'archived']);

        $response = $this->actingAs($admin)->patch(route('admin.teachers.reactivate', $teacher->id));

        $response->assertForbidden();
        $this->assertSame('archived', $teacher->refresh()->status);
    }

    public function test_reactivate_only_works_on_archived_accounts(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['status' => 'active']);

        $response = $this->actingAs($superAdmin)->patch(route('admin.teachers.reactivate', $teacher->id));

        $response->assertStatus(422);
        $this->assertSame('active', $teacher->refresh()->status);
    }

    public function test_reactivate_from_restricted_works(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['status' => 'restricted']);

        $response = $this->actingAs($superAdmin)->patch(route('admin.teachers.reactivate', $teacher->id));

        $response->assertOk();
        $this->assertSame('active', $teacher->refresh()->status);
    }

    public function test_restricted_teacher_cannot_log_in(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher([
            'username' => 'restrictedteacher',
            'email' => 'restricted@example.com',
            'password' => 'password123',
            'status' => 'restricted',
        ]);

        $response = $this->post(route('login.submit'), [
            'login' => 'restrictedteacher',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
        $this->assertNull(auth()->user());
    }

    public function test_status_dropdown_includes_restricted(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('admin.teachers'));
        $response->assertOk();
        $response->assertSee('Restricted');
    }

    public function test_fetch_endpoint_filters_by_restricted_status(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->createTeacher(['name' => 'Restricted Teacher', 'email' => 'restricted@example.com', 'status' => 'restricted']);
        $this->createTeacher(['name' => 'Active Teacher', 'email' => 'active@example.com', 'status' => 'active']);

        $response = $this->actingAs($superAdmin)->get(route('admin.teachers.fetch', ['status' => 'restricted']));
        $response->assertOk();
        $data = $response->json();
        $names = array_column($data['accounts'], 'name');
        $this->assertContains('Restricted Teacher', $names);
        $this->assertNotContains('Active Teacher', $names);
    }

    public function test_archived_accounts_endpoint_returns_archived(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->createTeacher(['name' => 'Archived Teacher', 'email' => 'archived@example.com', 'status' => 'archived']);
        $this->createTeacher(['name' => 'Active Teacher', 'email' => 'active@example.com', 'status' => 'active']);

        $response = $this->actingAs($superAdmin)->get(route('admin.teachers.archived'));
        $response->assertOk();
        $data = $response->json();
        $names = array_column($data['accounts'], 'name');
        $this->assertContains('Archived Teacher', $names);
        $this->assertNotContains('Active Teacher', $names);
    }

    public function test_archived_accounts_endpoint_search_filters(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->createTeacher(['name' => 'Archived Alice', 'email' => 'alice@example.com', 'status' => 'archived']);
        $this->createTeacher(['name' => 'Archived Bob', 'email' => 'bob@example.com', 'status' => 'archived']);

        $response = $this->actingAs($superAdmin)->get(route('admin.teachers.archived', ['search' => 'Alice']));
        $response->assertOk();
        $data = $response->json();
        $names = array_column($data['accounts'], 'name');
        $this->assertContains('Archived Alice', $names);
        $this->assertNotContains('Archived Bob', $names);
    }

    public function test_non_super_admin_cannot_view_archived_accounts(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.teachers.archived'));
        $response->assertForbidden();
    }

    public function test_unarchive_restores_active_status(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['status' => 'archived']);

        $this->assertSame('archived', $teacher->refresh()->status);

        $response = $this->actingAs($superAdmin)->patch(route('admin.teachers.reactivate', $teacher->id));

        $response->assertOk();
        $this->assertSame('active', $teacher->refresh()->status);
    }

    public function test_unarchived_teacher_can_log_in_again(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher([
            'username' => 'unarchiveteacher',
            'email' => 'unarchive@example.com',
            'password' => 'password123',
            'status' => 'archived',
        ]);

        $this->actingAs($superAdmin)->patch(route('admin.teachers.reactivate', $teacher->id));
        $this->assertSame('active', $teacher->refresh()->status);

        $response = $this->post(route('login.submit'), [
            'login' => 'unarchiveteacher',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertNotNull(auth()->user());
    }

    public function test_unarchive_preserves_student_records(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['status' => 'archived']);
        $student = $this->createStudent($teacher);

        $this->actingAs($superAdmin)->patch(route('admin.teachers.reactivate', $teacher->id));

        $teacher->refresh();
        $this->assertSame('active', $teacher->status);

        $student->refresh();
        $this->assertNotNull($student->id);
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_non_super_admin_cannot_unarchive(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher(['status' => 'archived']);

        $response = $this->actingAs($admin)->patch(route('admin.teachers.reactivate', $teacher->id));

        $response->assertForbidden();
        $this->assertSame('archived', $teacher->refresh()->status);
    }
}
