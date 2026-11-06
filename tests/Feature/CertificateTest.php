<?php

namespace Tests\Feature;

use App\Models\CertificateAward;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function fakeCertificate(): UploadedFile
    {
        return UploadedFile::fake()->create('certificate.pdf', 20, 'application/pdf');
    }

    public function test_teacher_uploads_certificate_for_own_student(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->post(route('admin.certificate.store'), [
            'student_id' => $student->id,
            'certificate_title' => 'Best Collector',
            'award_description' => 'Top bottle collector',
            'awarded_date' => now()->toDateString(),
            'certificate_file' => $this->fakeCertificate(),
        ]);

        $response->assertRedirect(route('admin.certificate'));

        $award = CertificateAward::where('student_id', $student->id)->first();
        $this->assertNotNull($award);
        $this->assertEquals($teacher->id, $award->teacher_id);
        $this->assertEquals($teacher->id, $award->issued_by);
        $this->assertSame('Best Collector', $award->award_title);
        $this->assertSame('Active', $award->status);
    }

    public function test_teacher_cannot_upload_certificate_for_another_teachers_student(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $studentOfA = $this->createStudent($teacherA);

        $response = $this->actingAs($teacherB)->post(route('admin.certificate.store'), [
            'student_id' => $studentOfA->id,
            'certificate_title' => 'Not Allowed',
            'awarded_date' => now()->toDateString(),
            'certificate_file' => $this->fakeCertificate(),
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('certificate_awards', ['award_title' => 'Not Allowed']);
    }

    public function test_certificate_list_is_scoped_for_teachers(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();

        $studentA = $this->createStudent($teacherA);

        CertificateAward::create([
            'student_id' => $studentA->id,
            'teacher_id' => $teacherA->id,
            'award_title' => 'Alpha Certificate',
            'awarded_date' => now()->toDateString(),
            'certificate_file' => 'certificates/a.pdf',
            'status' => 'Active',
            'issued_by' => $teacherA->id,
        ]);

        $response = $this->actingAs($teacherB)->get(route('admin.certificate'));

        $response->assertOk();
        $response->assertDontSee('Alpha Certificate');
    }
}
