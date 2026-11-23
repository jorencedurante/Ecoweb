<?php

namespace Tests\Feature;

use App\Mail\TeacherSetupInvitation;
use App\Models\BottleCollection;
use App\Models\Student;
use App\Models\User;
use App\Services\CollaborationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class Phase2CollaborationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function assign(User $subTeacher, User $teacher, string $level = 'collaborator', array $custom = []): User
    {
        $subTeacher->forceFill([
            'assigned_teacher_id' => $teacher->id,
            'sub_teacher_access_level' => $level,
            'sub_teacher_permissions' => $custom,
        ])->save();

        return $subTeacher;
    }

    private function subTeacher(array $overrides = []): User
    {
        return $this->createAccount(array_merge(['role' => 'sub_teacher'], $overrides));
    }

    public function test_sub_teacher_can_be_assigned_during_account_creation(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher(['name' => 'Kathleen Tabadero']);

        $response = $this->actingAs($admin)->postJson(route('admin.teachers.create-account'), [
            'name' => 'Maria Santos',
            'username' => 'maria.santos',
            'email' => 'maria-santos@example.com',
            'position' => 'Sub Teacher',
            'role' => 'sub_teacher',
            'teacher_id' => $teacher->id,
            'access_level' => 'collaborator',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $subTeacher = User::where('email', 'maria-santos@example.com')->firstOrFail();
        $this->assertSame('pending_setup', $subTeacher->status);
        $this->assertSame($teacher->id, (int) $subTeacher->assigned_teacher_id);
        $this->assertSame('collaborator', $subTeacher->sub_teacher_access_level);
        Mail::assertSent(TeacherSetupInvitation::class);
    }

    public function test_sub_teacher_can_be_assigned_after_account_creation(): void
    {
        $principal = $this->createSuperAdmin();
        $teacher = $this->createTeacher();
        $subTeacher = $this->subTeacher(['name' => 'Late Assign']);

        $this->actingAs($principal)->postJson(route('admin.sub-teacher-assignments.store'), [
            'sub_teacher_id' => $subTeacher->id,
            'teacher_id' => $teacher->id,
            'access_level' => 'view_only',
        ])->assertOk()->assertJsonPath('success', true);

        $subTeacher->refresh();
        $this->assertSame($teacher->id, (int) $subTeacher->assigned_teacher_id);
        $this->assertSame('view_only', $subTeacher->sub_teacher_access_level);
        $this->assertSame('Active', $subTeacher->assignmentStatus());
    }

    public function test_unassigned_sub_teacher_has_no_student_data_access(): void
    {
        $subTeacher = $this->subTeacher();
        $teacher = $this->createTeacher();
        $this->createStudent($teacher);

        $this->actingAs($subTeacher)->get(route('teacher.students'))
            ->assertOk()->assertSee('No Teacher Assignment Yet');
        $this->actingAs($subTeacher)->get(route('teacher.students.search', ['q' => 'a']))->assertForbidden();
        $this->actingAs($subTeacher)->get(route('teacher.bottle-collection'))
            ->assertOk()->assertSee('No Teacher Assignment Yet');
    }

    public function test_collaborator_can_view_but_manual_recording_is_disabled(): void
    {
        $teacher = $this->createTeacher(['name' => 'Kathleen Tabadero']);
        $subTeacher = $this->subTeacher();
        $this->assign($subTeacher, $teacher);
        $student = $this->createStudent($teacher);
        $studentCount = Student::count();

        $this->actingAs($subTeacher)->get(route('teacher.students'))
            ->assertOk()->assertSee($student->full_name);

        $this->actingAs($subTeacher)->post(route('teacher.bottle-collection.store'), [
            'student_id' => $student->id,
            'collection_date' => now()->toDateString(),
            'collection_time' => '10:00:00',
            'bottle_count' => 3,
        ])->assertForbidden();

        $this->assertSame(0, BottleCollection::count());
        $this->assertSame(0, (int) $student->refresh()->total_points);
        $this->assertSame($studentCount, Student::count(), 'No student rows may be duplicated.');
    }

    public function test_view_only_cannot_modify_shared_records(): void
    {
        $teacher = $this->createTeacher();
        $subTeacher = $this->subTeacher();
        $this->assign($subTeacher, $teacher, 'view_only');
        $student = $this->createStudent($teacher);

        $this->actingAs($subTeacher)->get(route('teacher.students'))->assertOk();

        $this->actingAs($subTeacher)->post(route('teacher.bottle-collection.store'), [
            'student_id' => $student->id,
            'collection_date' => now()->toDateString(),
            'collection_time' => '10:00:00',
            'bottle_count' => 1,
        ])->assertForbidden();

        $this->actingAs($subTeacher)->post(route('teacher.students.store'), [
            'first_name' => 'Denied', 'last_name' => 'Sub', 'lrn' => '99999999999',
            'grade_level' => 'Grade 1', 'gender' => 'Male', 'school_year' => '2026-2027',
        ])->assertForbidden();

        $this->assertSame(0, BottleCollection::count());
        $this->assertSame(1, Student::count());
    }

    public function test_custom_permissions_are_enforced_server_side(): void
    {
        $teacher = $this->createTeacher();
        $subTeacher = $this->subTeacher();
        $this->assign($subTeacher, $teacher, 'custom', ['view_students', 'view_reports']);
        $student = $this->createStudent($teacher);

        $this->actingAs($subTeacher)->get(route('teacher.students'))->assertOk();
        // Report data also requires view_reports; asserting here via the service
        // avoids the MySQL-only chart queries used by the report page.
        $this->assertTrue(app(CollaborationService::class)->can($subTeacher, 'view_reports'));

        $this->actingAs($subTeacher)->post(route('teacher.bottle-collection.store'), [
            'student_id' => $student->id,
            'collection_date' => now()->toDateString(),
            'collection_time' => '10:00:00',
            'bottle_count' => 2,
        ])->assertForbidden();

        $this->assertSame(0, BottleCollection::count());
    }

    public function test_sub_teacher_cannot_access_another_teachers_records_by_modified_url(): void
    {
        $ownTeacher = $this->createTeacher(['name' => 'Own Teacher']);
        $otherTeacher = $this->createTeacher(['name' => 'Other Teacher']);
        $subTeacher = $this->subTeacher();
        $this->assign($subTeacher, $ownTeacher);
        $otherStudent = $this->createStudent($otherTeacher);

        $this->actingAs($subTeacher)->get(route('teacher.students.info', $otherStudent))->assertForbidden();
        $this->actingAs($subTeacher)->get(route('teacher.students.overview', $otherStudent))->assertForbidden();

        $this->actingAs($subTeacher)->post(route('teacher.bottle-collection.store'), [
            'student_id' => $otherStudent->id,
            'collection_date' => now()->toDateString(),
            'collection_time' => '10:00:00',
            'bottle_count' => 1,
        ])->assertForbidden();
    }

    public function test_teacher_and_sub_teacher_cannot_manage_assignments(): void
    {
        $teacher = $this->createTeacher();
        $subTeacher = $this->subTeacher();

        $this->actingAs($teacher)->get(route('admin.sub-teacher-assignments'))->assertForbidden();
        $this->actingAs($teacher)->post(route('admin.sub-teacher-assignments.store'), [])->assertForbidden();
        $this->actingAs($subTeacher)->get(route('admin.sub-teacher-assignments'))->assertForbidden();
    }

    public function test_admin_can_update_assignment_and_access_level(): void
    {
        $principal = $this->createSuperAdmin();
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $subTeacher = $this->subTeacher();
        $assignment = $this->assign($subTeacher, $teacherA);

        $this->actingAs($principal)->putJson(route('admin.sub-teacher-assignments.update', $assignment), [
            'teacher_id' => $teacherB->id,
            'access_level' => 'view_only',
        ])->assertOk()->assertJsonPath('success', true);

        $assignment->refresh();
        $this->assertSame($teacherB->id, (int) $assignment->assigned_teacher_id);
        $this->assertSame('view_only', $assignment->sub_teacher_access_level);
    }

    public function test_reassignment_revokes_previous_teacher_access(): void
    {
        $teacherA = $this->createTeacher(['name' => 'Teacher A']);
        $teacherB = $this->createTeacher(['name' => 'Teacher B']);
        $subTeacher = $this->subTeacher();
        $assignment = $this->assign($subTeacher, $teacherA);
        $studentA = $this->createStudent($teacherA);
        $studentB = $this->createStudent($teacherB);

        $this->actingAs($subTeacher)->get(route('teacher.students'))->assertSee($studentA->full_name);
        $this->actingAs($subTeacher)->get(route('teacher.students.info', $studentB))->assertForbidden();

        $assignment->forceFill(['assigned_teacher_id' => $teacherB->id])->save();

        $this->actingAs($subTeacher)->get(route('teacher.students.info', $studentA))->assertForbidden();
        $this->actingAs($subTeacher)->get(route('teacher.students.info', $studentB))->assertOk();
    }

    public function test_unassigning_revokes_all_access(): void
    {
        $teacher = $this->createTeacher();
        $subTeacher = $this->subTeacher();
        $assignment = $this->assign($subTeacher, $teacher);
        $student = $this->createStudent($teacher);

        $this->actingAs($subTeacher)->get(route('teacher.students.info', $student))->assertOk();

        $this->actingAs($this->createSuperAdmin())
            ->deleteJson(route('admin.sub-teacher-assignments.destroy', $assignment))
            ->assertOk()->assertJsonPath('success', true);

        $this->actingAs($subTeacher)->get(route('teacher.students'))
            ->assertOk()->assertSee('No Teacher Assignment Yet');
    }

    public function test_archiving_assigned_teacher_or_sub_teacher_revokes_access(): void
    {
        $teacher = $this->createTeacher();
        $subTeacher = $this->subTeacher();
        $this->assign($subTeacher, $teacher);
        $student = $this->createStudent($teacher);

        $this->actingAs($subTeacher)->get(route('teacher.students.info', $student))->assertOk();

        $teacher->forceFill(['status' => 'archived'])->save();
        $this->actingAs($subTeacher)->get(route('teacher.students'))
            ->assertOk()->assertSee('No Teacher Assignment Yet');
    }

    public function test_shared_records_are_visible_to_the_owner_teacher(): void
    {
        $teacher = $this->createTeacher();
        $subTeacher = $this->subTeacher();
        $this->assign($subTeacher, $teacher);
        $student = $this->createStudent($teacher);

        $enrollment = $student->enrollments()->where('status', 'active')->latest('id')->firstOrFail();
        BottleCollection::create([
            'student_id' => $student->id,
            'lrn' => $student->lrn,
            'collection_date' => now()->toDateString(),
            'collection_time' => '11:00:00',
            'bottle_count' => 5,
            'points_earned' => 5,
            'created_by' => $subTeacher->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $this->actingAs($teacher)->getJson(route('teacher.bottle-collection.fetch'))
            ->assertOk()
            ->assertJsonPath('records.0.bottle_count', 5)
            ->assertJsonPath('records.0.recorded_by', $subTeacher->name);
    }

    public function test_sub_teacher_invitation_and_activation_still_work(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();

        $this->actingAs($admin)->postJson(route('admin.teachers.create-account'), [
            'name' => 'Activated Sub',
            'username' => 'activated.sub',
            'email' => 'activated-sub@example.com',
            'position' => 'Sub Teacher',
            'role' => 'sub_teacher',
            'teacher_id' => $teacher->id,
        ])->assertOk();

        $subTeacher = User::where('email', 'activated-sub@example.com')->firstOrFail();
        $mail = Mail::sent(TeacherSetupInvitation::class)->first();
        $this->assertNotNull($mail);
        $setupUrl = $mail->setupUrl;

        // The setup form posts back to the signed invitation URL itself, as a guest.
        auth()->logout();
        $this->post($setupUrl, [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertSame('active', $subTeacher->refresh()->status);
        $this->assertTrue(Hash::check('new-password-123', $subTeacher->password));
        $this->assertTrue($subTeacher->fresh()->hasEffectiveAssignment());
    }

    public function test_existing_teacher_functions_remain_unchanged(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $this->actingAs($teacher)->get(route('teacher.students'))->assertOk()->assertSee($student->full_name);
        $this->actingAs($teacher)->post(route('teacher.bottle-collection.store'), [
            'student_id' => $student->id,
            'collection_date' => now()->toDateString(),
            'collection_time' => '09:00:00',
            'bottle_count' => 2,
        ])->assertForbidden();

        $this->assertSame(0, BottleCollection::count());
    }

    public function test_assignment_management_page_and_permissions_metadata(): void
    {
        $principal = $this->createSuperAdmin();
        $subTeacher = $this->subTeacher();
        $teacher = $this->createTeacher();
        $this->assign($subTeacher, $teacher);

        $this->actingAs($principal)->get(route('admin.sub-teacher-assignments'))
            ->assertOk()->assertSee('Sub Teacher Assignments')->assertSee($subTeacher->name);

        $this->assertSame([
            'collaborator' => 'Collaborator',
            'view_only' => 'View Only',
            'custom' => 'Custom',
        ], CollaborationService::accessLevels());

        $this->assertContains(
            CollaborationService::RECORD_BOTTLE_COLLECTION,
            CollaborationService::permissionsFor('collaborator')
        );
        $this->assertNotContains(
            CollaborationService::RECORD_BOTTLE_COLLECTION,
            CollaborationService::permissionsFor('view_only')
        );
        $this->assertNotContains(
            CollaborationService::MANAGE_CERTIFICATES,
            CollaborationService::permissionsFor('custom', ['view_students'])
        );
    }
}
