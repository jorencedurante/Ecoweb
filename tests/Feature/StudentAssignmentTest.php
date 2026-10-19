<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentEnrollment;
use Tests\TestCase;

class StudentAssignmentTest extends TestCase
{
    public function test_student_can_be_assigned_to_a_teacher_account(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher();

        $response = $this->actingAs($superAdmin)->post(route('admin.students.store'), [
            'lrn' => '123456789012',
            'first_name' => 'Assigned',
            'last_name' => 'Student',
            'grade_level' => 'Grade 2',
            'gender' => 'Female',
            'teacher_id' => $teacher->id,
        ]);

        $response->assertRedirect(route('admin.students'));

        $student = Student::where('lrn', '123456789012')->first();
        $this->assertNotNull($student);

        // The class assignment lives in student_enrollments and points at
        // the teacher's users row.
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);
    }

    public function test_student_cannot_be_assigned_to_an_admin_account(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $admin = $this->createAdmin();

        $response = $this->actingAs($superAdmin)->post(route('admin.students.store'), [
            'lrn' => '223456789012',
            'first_name' => 'Bad',
            'last_name' => 'Assignment',
            'grade_level' => 'Grade 2',
            'gender' => 'Male',
            'teacher_id' => $admin->id,
        ]);

        $response->assertSessionHasErrors('teacher_id');
        $this->assertDatabaseMissing('students', ['lrn' => '223456789012']);
    }

    public function test_student_update_cannot_reassign_to_an_admin_account(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $admin = $this->createAdmin();
        $student = $this->createStudent();

        $response = $this->actingAs($superAdmin)->put(route('admin.students.update', $student->id), [
            'lrn' => $student->lrn,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'grade_level' => 'Grade 2',
            'gender' => 'Male',
            'teacher_id' => $admin->id,
        ]);

        $response->assertSessionHasErrors('teacher_id');
    }

    public function test_teacher_sees_only_assigned_students(): void
    {
        $teacherA = $this->createTeacher(['name' => 'Teacher Alpha']);
        $teacherB = $this->createTeacher(['name' => 'Teacher Beta']);

        $this->createStudent($teacherA, ['first_name' => 'AliceA', 'last_name' => 'OneA']);
        $this->createStudent($teacherB, ['first_name' => 'BobB', 'last_name' => 'TwoB']);

        $response = $this->actingAs($teacherB)->get(route('admin.students'));

        $response->assertOk();
        $response->assertSee('BobB');
        $response->assertDontSee('AliceA');
    }

    public function test_teacher_cannot_open_another_teachers_student_info(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $studentOfA = $this->createStudent($teacherA);

        $response = $this->actingAs($teacherB)->get(route('students.info', $studentOfA->id));

        $response->assertForbidden();
    }

    public function test_teacher_can_open_own_student_info(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->get(route('students.info', $student->id));

        $response->assertOk();
    }

    public function test_admin_accounts_are_view_only_for_students(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.students.store'), [
            'lrn' => '323456789012',
            'first_name' => 'Blocked',
            'last_name' => 'Creation',
            'grade_level' => 'Grade 1',
            'gender' => 'Male',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('students', ['lrn' => '323456789012']);
    }

    public function test_assignments_are_stored_in_the_users_linked_enrollment(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher();

        $this->actingAs($superAdmin)->post(route('admin.students.store'), [
            'lrn' => '423456789012',
            'first_name' => 'Enrolled',
            'last_name' => 'Learner',
            'grade_level' => 'Grade 3',
            'gender' => 'Female',
            'teacher_id' => $teacher->id,
        ]);

        $enrollment = StudentEnrollment::where('teacher_id', $teacher->id)->latest('id')->first();

        $this->assertNotNull($enrollment);
        $this->assertSame('Grade 3', $enrollment->grade_level);
        $this->assertEquals($teacher->id, $enrollment->teacher->id);
    }
}
