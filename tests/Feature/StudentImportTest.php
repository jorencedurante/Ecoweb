<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Common\Entity\Row;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    private function makeImportFile(string $lrn): UploadedFile
    {
        Storage::fake('local');
        Storage::fake('public');

        $path = storage_path('framework/testing/import_' . $lrn . '.xlsx');

        $writer = new XLSXWriter();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['LRN', 'First Name', 'Last Name', 'Grade Level', 'Gender']));
        $writer->addRow(Row::fromValues([$lrn, 'Imported', 'Learner', 'Grade 4', 'Female']));
        $writer->close();

        return new UploadedFile($path, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_teacher_import_assigns_students_to_the_importing_teacher(): void
    {
        $teacher = $this->createTeacher();

        $response = $this->actingAs($teacher)->post(route('admin.students.import'), [
            'file' => $this->makeImportFile('512345678901'),
        ]);

        $response->assertRedirect(route('admin.students'));

        $student = Student::where('lrn', '512345678901')->first();
        $this->assertNotNull($student);

        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'grade_level' => 'Grade 4',
            'status' => 'active',
        ]);
    }

    public function test_super_admin_import_does_not_assign_students_to_an_admin_account(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->post(route('admin.students.import'), [
            'file' => $this->makeImportFile('512345678902'),
        ]);

        $response->assertRedirect(route('admin.students'));

        $student = Student::where('lrn', '512345678902')->first();
        $this->assertNotNull($student);

        // Admin/super-admin imports must never assign the student to an
        // admin account (the old code assigned user id 1 automatically).
        $this->assertDatabaseMissing('student_enrollments', [
            'student_id' => $student->id,
            'teacher_id' => $superAdmin->id,
        ]);
        $this->assertSame(
            0,
            \App\Models\StudentEnrollment::where('student_id', $student->id)->count()
        );
    }
}
