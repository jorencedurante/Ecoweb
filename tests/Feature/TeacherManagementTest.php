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

        $response = $this->actingAs($superAdmin)->delete(route('admin.teachers.destroy', $teacher->id));

        $response->assertRedirect(route('admin.teachers'));
        $this->assertSame('archived', $teacher->refresh()->status);
    }

    public function test_last_super_admin_cannot_be_archived(): void
    {
        $superAdmin = $this->createSuperAdmin();

        // Ensure this account is the only Super Admin in the database,
        // regardless of any pre-existing seeded data.
        User::where('role', 'super_admin')
            ->where('id', '!=', $superAdmin->id)
            ->update(['role' => 'admin']);

        $response = $this->actingAs($superAdmin)->delete(route('admin.teachers.destroy', $superAdmin->id));

        $response->assertSessionHasErrors('role');
        $this->assertSame('active', $superAdmin->refresh()->status);
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
}
