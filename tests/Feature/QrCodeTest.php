<?php

namespace Tests\Feature;

use App\Models\QrCode;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QrCodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_teacher_generates_qr_code_for_own_student(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->post(route('admin.qrcode.generate'), [
            'student_id' => $student->id,
        ]);

        $response->assertRedirect(route('admin.qrcode'));

        $qr = QrCode::where('student_id', $student->id)->first();
        $this->assertNotNull($qr);
        $this->assertSame($teacher->id, $qr->created_by);
        $this->assertStringContainsString($student->lrn, $qr->qr_value);
    }

    public function test_teacher_cannot_generate_qr_for_another_teachers_student(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $studentOfA = $this->createStudent($teacherA);

        $response = $this->actingAs($teacherB)->post(route('admin.qrcode.generate'), [
            'student_id' => $studentOfA->id,
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('qr_codes', ['student_id' => $studentOfA->id]);
    }

    public function test_qr_page_lists_only_students_of_the_teacher(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();

        $studentA = $this->createStudent($teacherA, ['first_name' => 'QrAlpha', 'last_name' => 'One']);
        $this->createStudent($teacherB, ['first_name' => 'QrBeta', 'last_name' => 'Two']);

        $this->actingAs($teacherA)->post(route('admin.qrcode.generate'), ['student_id' => $studentA->id]);

        // Drop the flash message from the generation request so only the
        // page contents are asserted.
        $this->flushSession();

        $response = $this->actingAs($teacherB)->get(route('admin.qrcode'));

        $response->assertOk();
        $response->assertSee('QrBeta');
        $response->assertDontSee('QrAlpha');
    }
}
