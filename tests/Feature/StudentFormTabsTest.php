<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentEnrollment;
use Tests\TestCase;

class StudentFormTabsTest extends TestCase
{
    private function validStudentPayload(array $overrides = []): array
    {
        return array_merge([
            'lrn' => '998877665544',
            'first_name' => 'Tabbed',
            'middle_name' => 'M.',
            'last_name' => 'Student',
            'grade_level' => 'Grade 4',
            'gender' => 'Female',
            'birth_date' => '2015-06-15',
            'section' => 'Rizal',
            'school_year' => '2026-2027',
            'house_street' => '123 Mabini St',
            'barangay' => 'Poblacion',
            'municipality_city' => 'Quezon City',
            'province' => 'Metro Manila',
            'region' => 'NCR',
            'father_name' => 'Juan Dela Cruz',
            'mother_maiden_name' => 'Maria Santos',
            'guardian_name' => 'Maria Dela Cruz',
            'guardian_relationship' => 'Mother',
            'contact_number' => '09171234567',
            'ip_ethnic_group' => 'Tagalog',
            'religion' => 'Catholic',
            'nationality' => 'Filipino',
            'learning_modality' => 'Face-to-face',
            'remarks' => 'Honor student.',
        ], $overrides);
    }

    public function test_teacher_adds_student_with_all_tab_fields_and_is_auto_assigned(): void
    {
        $teacher = $this->createTeacher();

        $response = $this->actingAs($teacher)->post(route('admin.students.store'), $this->validStudentPayload());

        $response->assertRedirect(route('admin.students'));

        $student = Student::where('lrn', '998877665544')->first();
        $this->assertNotNull($student);

        // New teacher-enrolled students are Active, no manual status field.
        $this->assertSame('Active', $student->status);
        $this->assertSame($teacher->id, $student->teacher_id);

        // Profile fields from all four tabs are stored on the students row.
        $this->assertSame('2015-06-15', $student->birth_date->format('Y-m-d'));
        $this->assertSame('123 Mabini St', $student->house_street);
        $this->assertSame('NCR', $student->region);
        $this->assertSame('Maria Dela Cruz', $student->guardian_name);
        $this->assertSame('Filipino', $student->nationality);
        $this->assertSame('Face-to-face', $student->learning_modality);

        // Section and school year live on the enrollment row.
        $enrollment = StudentEnrollment::where('student_id', $student->id)->first();
        $this->assertNotNull($enrollment);
        $this->assertSame($teacher->id, $enrollment->teacher_id);
        $this->assertSame('Rizal', $enrollment->section);
        $this->assertSame('2026-2027', $enrollment->school_year);
        $this->assertSame('active', $enrollment->status);

        // Exactly one assignment record — no duplicates.
        $this->assertSame(1, StudentEnrollment::where('student_id', $student->id)->count());
    }

    public function test_teachers_cannot_enroll_students_into_another_teachers_class(): void
    {
        $teacher = $this->createTeacher();
        $other = $this->createTeacher();

        $this->actingAs($teacher)->post(route('admin.students.store'), $this->validStudentPayload([
            'lrn' => '998877665545',
            'teacher_id' => $other->id,
        ]));

        $student = Student::where('lrn', '998877665545')->first();
        $this->assertSame($teacher->id, $student->teacher_id);
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
        ]);
        $this->assertDatabaseMissing('student_enrollments', [
            'student_id' => $student->id,
            'teacher_id' => $other->id,
        ]);
    }

    public function test_duplicate_lrn_is_rejected_and_reopens_the_add_modal(): void
    {
        $teacher = $this->createTeacher();
        $existing = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->post(route('admin.students.store'), $this->validStudentPayload([
            'lrn' => $existing->lrn,
        ]));

        $response->assertSessionHasErrors('lrn');
        $response->assertSessionHas('open_modal', 'add');
    }

    public function test_teacher_updates_profile_and_enrollment_without_duplicates(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->put(
            route('admin.students.update', $student->id),
            $this->validStudentPayload([
                'lrn' => $student->lrn,
                'first_name' => 'Updated',
                'grade_level' => 'Grade 5',
                'section' => 'Bonifacio',
                'school_year' => '2027-2028',
            ])
        );

        $response->assertRedirect(route('admin.students'));

        $student->refresh();
        $this->assertSame('Updated', $student->first_name);
        $this->assertSame('Updated M. Student', $student->full_name);
        $this->assertSame('NCR', $student->region);
        $this->assertSame('Filipino', $student->nationality);
        $this->assertSame('Active', $student->status);

        // The existing enrollment is updated in place — still exactly one row.
        $this->assertSame(1, StudentEnrollment::where('student_id', $student->id)->count());
        $enrollment = StudentEnrollment::where('student_id', $student->id)->first();
        $this->assertSame('Grade 5', $enrollment->grade_level);
        $this->assertSame('Bonifacio', $enrollment->section);
        $this->assertSame('2027-2028', $enrollment->school_year);
    }

    public function test_editing_does_not_reactivate_an_archived_student(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, ['status' => 'Archived']);

        $this->actingAs($teacher)->put(
            route('admin.students.update', $student->id),
            $this->validStudentPayload([
                'lrn' => $student->lrn,
                'first_name' => 'StillArchived',
            ])
        );

        $this->assertSame('Archived', $student->refresh()->status);
        $this->assertSame('StillArchived', $student->first_name);
    }

    public function test_failed_update_reopens_the_edit_modal_for_that_student(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->put(
            route('admin.students.update', $student->id),
            $this->validStudentPayload(['lrn' => ''])
        );

        $response->assertSessionHasErrors('lrn');
        $response->assertSessionHas('open_modal', 'edit');
        $response->assertSessionHas('open_modal_student_id', $student->id);
    }

    public function test_overview_returns_new_profile_fields_for_full_details(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, [
            'region' => 'Region IV-A',
            'nationality' => 'Filipino',
            'house_street' => '45 Rizal St',
        ]);

        $response = $this->actingAs($teacher)->getJson(route('admin.students.overview', $student->id));

        $response->assertOk();
        $this->assertSame('Region IV-A', $response->json('student.region'));
        $this->assertSame('Filipino', $response->json('student.nationality'));
        $this->assertSame('45 Rizal St', $response->json('student.house_street'));
    }

    public function test_students_page_renders_four_form_tabs_without_teacher_dropdown(): void
    {
        $teacher = $this->createTeacher();
        $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->get(route('admin.students'));

        $response->assertOk();
        $response->assertSee('data-form-tab="basic"', false);
        $response->assertSee('data-form-tab="address"', false);
        $response->assertSee('data-form-tab="family"', false);
        $response->assertSee('data-form-tab="other"', false);
        $response->assertSee('name="section"', false);
        $response->assertSee('name="school_year"', false);
        $response->assertSee('name="region"', false);
        $response->assertSee('name="nationality"', false);
        // No Assigned Teacher input in either modal.
        $response->assertDontSee('name="teacher_id"', false);
    }
}
