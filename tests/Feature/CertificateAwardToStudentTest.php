<?php

namespace Tests\Feature;

use App\Models\CertificateAward;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateAwardToStudentTest extends TestCase
{
    /**
     * Minimal valid 1x1 baseline JPEG (RGB) used to exercise the JPEG -> PDF
     * wrapper without requiring the GD extension.
     */
    private const ONE_PIXEL_JPEG = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigD//2Q==';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    private function realJpeg(string $name = 'certificate.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::ONE_PIXEL_JPEG));
    }

    private function canvasData(int $studentId, int $adviserId, array $overrides = []): string
    {
        $studentBox = array_merge([
            'type' => 'student_name',
            'student_id' => (string) $studentId,
            'text' => 'Student One LastName1',
            'left' => 36.61,
            'top' => 57.3,
            'width' => 30,
            'height' => 7,
            'baseline' => 64.32,
            'fontSize' => 24,
            'fontSizePref' => 24,
            'fontSizeMin' => 16,
            'fontFamily' => 'Georgia',
            'color' => '#1F2937',
            'fontWeight' => '700',
            'textAlign' => 'center',
        ], $overrides);

        $adviserBox = [
            'type' => 'adviser_name',
            'teacher_id' => (string) $adviserId,
            'text' => 'Adviser Name',
            'left' => 19.5,
            'top' => 87,
            'width' => 13.25,
            'height' => 5.3,
            'baseline' => 92.32,
            'fontSize' => 18,
            'fontSizePref' => 18,
            'fontSizeMin' => 10,
            'fontFamily' => 'Georgia',
            'color' => '#1F2937',
            'fontWeight' => '700',
            'textAlign' => 'center',
        ];

        return json_encode([$studentBox, $adviserBox]);
    }

    private function awardPayload(Student $student, User $adviser, array $overrides = []): array
    {
        return array_merge([
            'student_id' => $student->id,
            'adviser_id' => $adviser->id,
            'template_file' => $this->realJpeg('template.jpg'),
            'generated_file' => $this->realJpeg(),
            'canvas_data' => $this->canvasData($student->id, $adviser->id),
        ], $overrides);
    }

    private function awardCertificate(User $acting, Student $student, User $adviser, array $overrides = []): CertificateAward
    {
        $response = $this->actingAs($acting)
            ->postJson(route('admin.certificate.award'), $this->awardPayload($student, $adviser, $overrides));

        $response->assertOk()->assertJsonPath('success', true)->assertJsonPath('duplicate', false);

        return CertificateAward::findOrFail($response->json('id'));
    }

    public function test_generator_page_shows_award_to_student_button(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.certificate'));

        $response->assertOk();
        $response->assertSee('id="awardToStudentBtn"', false);
        $response->assertSee('Award to Student', false);
        $response->assertSee('id="awardStatus"', false);
        $response->assertSee('compositeCertificate', false);
    }

    public function test_award_creates_record_with_private_files_and_pdf(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();

        $award = $this->awardCertificate($admin, $student, $adviser);

        $this->assertDatabaseHas('certificate_awards', [
            'id' => $award->id,
            'student_id' => $student->id,
            'issued_by' => $admin->id,
            'award_title' => 'Certificate',
            'signed_by' => $adviser->name,
            'status' => 'Active',
        ]);
        $this->assertSame(now()->toDateString(), $award->awarded_date->toDateString());
        $this->assertNull($award->teacher_id, 'Administrators do not get a teacher_id on awards.');
        $this->assertNotNull($award->canvas_data);

        $this->assertStringStartsWith('certificate-awards/templates/', $award->template_file);
        $this->assertStringStartsWith('certificate-awards/generated/', $award->generated_file_path);
        $this->assertStringStartsWith('certificate-awards/pdf/', $award->pdf_file_path);

        $this->assertTrue(Storage::disk('local')->exists($award->template_file));
        $this->assertTrue(Storage::disk('local')->exists($award->generated_file_path));
        $this->assertTrue(Storage::disk('local')->exists($award->pdf_file_path));
        $this->assertFalse(Storage::disk('public')->exists($award->generated_file_path), 'Output must not be on the public disk.');

        $pdf = Storage::disk('local')->get($award->pdf_file_path);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringEndsWith('%%EOF', $pdf);

        $this->assertDatabaseHas('admin_activities', [
            'user_id' => $admin->id,
            'action' => 'Awarded Certificate',
            'module' => 'Certificate',
        ]);
    }

    public function test_award_requires_student_adviser_and_files(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson(route('admin.certificate.award'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_id', 'adviser_id', 'template_file', 'generated_file', 'canvas_data']);
        $this->assertSame(0, CertificateAward::count());
    }

    public function test_award_rejects_unavailable_adviser(): void
    {
        $admin = $this->createAdmin();
        $inactiveAdviser = $this->createTeacher(['status' => 'suspended']);
        $student = $this->createStudent();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.certificate.award'), $this->awardPayload($student, $inactiveAdviser));

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The selected adviser is not available to sign certificates.');
        $this->assertSame(0, CertificateAward::count());
    }

    public function test_award_rejects_layout_that_does_not_match_selected_student(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $otherStudent = $this->createStudent();

        $mismatched = $this->canvasData($otherStudent->id, $adviser->id);
        $response = $this->actingAs($admin)->postJson(
            route('admin.certificate.award'),
            $this->awardPayload($student, $adviser, ['canvas_data' => $mismatched])
        );

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The student name on the certificate does not match the selected student.');
        $this->assertSame(0, CertificateAward::count());
    }

    public function test_award_rejects_layout_without_valid_positions(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();

        $broken = json_encode([
            [
                'type' => 'student_name',
                'student_id' => (string) $student->id,
                'text' => 'Student One LastName1',
                'width' => 30,
            ],
            [
                'type' => 'adviser_name',
                'teacher_id' => (string) $adviser->id,
                'text' => 'Adviser Name',
                'left' => 19.5,
                'top' => 87,
                'width' => 13.25,
            ],
        ]);

        $response = $this->actingAs($admin)->postJson(
            route('admin.certificate.award'),
            $this->awardPayload($student, $adviser, ['canvas_data' => $broken])
        );

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Invalid certificate layout data.');
        $this->assertSame(0, CertificateAward::count());
    }

    public function test_repeated_identical_submission_does_not_duplicate(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();

        $first = $this->actingAs($admin)
            ->postJson(route('admin.certificate.award'), $this->awardPayload($student, $adviser));
        $first->assertOk()->assertJsonPath('success', true)->assertJsonPath('duplicate', false);

        $second = $this->actingAs($admin)
            ->postJson(route('admin.certificate.award'), $this->awardPayload($student, $adviser));
        $second->assertOk()->assertJsonPath('success', true)->assertJsonPath('duplicate', true);
        $second->assertJsonPath('id', $first->json('id'));

        $this->assertSame(1, CertificateAward::count());

        // A changed layout for the same student is a different certificate.
        $changedLayout = $this->canvasData($student->id, $adviser->id, ['top' => 60]);
        $third = $this->actingAs($admin)->postJson(
            route('admin.certificate.award'),
            $this->awardPayload($student, $adviser, ['canvas_data' => $changedLayout])
        );
        $third->assertOk()->assertJsonPath('duplicate', false);
        $this->assertSame(2, CertificateAward::count());
    }

    public function test_teacher_awards_only_for_assigned_students(): void
    {
        $teacher = $this->createTeacher();
        $adviser = $this->createTeacher();
        $enrolledStudent = $this->createStudent($teacher);
        $foreignStudent = $this->createStudent();

        $allowed = $this->actingAs($teacher)
            ->postJson(route('admin.certificate.award'), $this->awardPayload($enrolledStudent, $adviser));
        $allowed->assertOk();
        $this->assertSame(1, CertificateAward::count());

        $denied = $this->actingAs($teacher)
            ->postJson(route('admin.certificate.award'), $this->awardPayload($foreignStudent, $adviser));
        $denied->assertStatus(404);
        $this->assertSame(1, CertificateAward::count());

        $award = CertificateAward::first();
        $this->assertSame($teacher->id, $award->teacher_id);
    }

    public function test_download_print_and_file_serve_the_awarded_certificate(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $award = $this->awardCertificate($admin, $student, $adviser);

        $download = $this->actingAs($admin)->get(route('admin.certificate.download', $award));
        $download->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('content-disposition'));
        $this->assertStringContainsString('Certificate-', (string) $download->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $download->streamedContent());

        $file = $this->actingAs($admin)->get(route('admin.certificate.file', $award));
        $file->assertOk();
        $this->assertStringStartsWith('image/jpeg', (string) $file->headers->get('content-type'));

        $print = $this->actingAs($admin)->get(route('admin.certificate.print', $award));
        $print->assertOk();
        $print->assertSee(route('admin.certificate.file', $award), false);
        $print->assertDontSee('storage/certificate-awards', false);
    }

    public function test_print_view_falls_back_to_legacy_reconstruction_without_generated_file(): void
    {
        $teacher = $this->createTeacher();
        $adviser = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->post(route('admin.certificate.save-canvas'), [
            'student_id' => $student->id,
            'adviser_id' => $adviser->id,
            'template_file' => UploadedFile::fake()->create('t.jpg', 10, 'image/jpeg'),
            'canvas_data' => $this->canvasData($student->id, $adviser->id),
        ]);
        $response->assertRedirect();

        $award = CertificateAward::latest('id')->first();
        $this->assertNull($award->generated_file_path);

        $print = $this->actingAs($teacher)->get(route('admin.certificate.print', $award));
        $print->assertOk();
        $print->assertSee('certificate-student-name-print', false);
        $print->assertSee('certificate-adviser-print', false);
    }

    public function test_teacher_cannot_access_foreign_certificate_files(): void
    {
        $owner = $this->createTeacher();
        $other = $this->createTeacher();
        $adviser = $this->createTeacher();
        $student = $this->createStudent($owner);
        $award = $this->awardCertificate($owner, $student, $adviser);

        $this->actingAs($other)->get(route('admin.certificate.download', $award))->assertForbidden();
        $this->actingAs($other)->get(route('admin.certificate.print', $award))->assertForbidden();
        $this->actingAs($other)->get(route('admin.certificate.file', $award))->assertForbidden();
    }

    public function test_student_overview_lists_awarded_certificate_with_authorized_links(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $award = $this->awardCertificate($admin, $student, $adviser);

        $response = $this->actingAs($admin)->get(route('admin.students.overview', $student));

        $response->assertOk();
        $response->assertJsonPath('certificates.0.id', $award->id);
        $response->assertJsonPath('certificates.0.title', 'Certificate');
        $response->assertJsonPath('certificates.0.status', 'Active');
        $response->assertJsonPath('certificates.0.awarded_date', now()->format('M d, Y'));
        $response->assertJsonPath('certificates.0.view_url', route('admin.certificate.download', $award));
        $response->assertJsonPath('certificates.0.print_url', route('admin.certificate.print', $award));
        $response->assertDontSee('storage/certificate-awards', false);
    }
}
