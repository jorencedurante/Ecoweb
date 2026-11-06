<?php

namespace Tests\Feature;

use App\Models\CertificateAward;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CertificateAccessTest extends TestCase
{
    /** Minimal valid 1x1 baseline JPEG (RGB). */
    private const ONE_PIXEL_JPEG = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEIIxwVVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEkRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigD//2Q==';

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

    private function canvasData(int $studentId, int $adviserId): array
    {
        return [
            [
                'type' => 'student_name',
                'student_id' => (string) $studentId,
                'text' => 'Kathleen Tabadero',
                'left' => 36.61,
                'top' => 57.3,
                'width' => 30,
                'height' => 7,
                'fontSize' => 24,
                'fontSizePref' => 24,
                'fontSizeMin' => 16,
                'fontFamily' => 'Georgia',
                'color' => '#1F2937',
                'fontWeight' => '700',
                'textAlign' => 'center',
            ],
            [
                'type' => 'adviser_name',
                'teacher_id' => (string) $adviserId,
                'text' => 'Maria Santos',
                'left' => 19.5,
                'top' => 87,
                'width' => 13.25,
                'height' => 5.3,
                'fontSize' => 18,
                'fontSizePref' => 18,
                'fontSizeMin' => 10,
                'fontFamily' => 'Georgia',
                'color' => '#1F2937',
                'fontWeight' => '700',
                'textAlign' => 'center',
            ],
        ];
    }

    private function awardCertificate(User $acting, Student $student, User $adviser): CertificateAward
    {
        $response = $this->actingAs($acting)->postJson(route('admin.certificate.award'), [
            'student_id' => $student->id,
            'adviser_id' => $adviser->id,
            'template_file' => $this->realJpeg('template.jpg'),
            'generated_file' => $this->realJpeg(),
            'canvas_data' => json_encode($this->canvasData($student->id, $adviser->id)),
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        return CertificateAward::findOrFail($response->json('id'));
    }

    private function legacyCanvasAward(Student $student, User $adviser, array $overrides = []): CertificateAward
    {
        $templatePath = 'certificate-templates/legacy-' . $student->id . '.jpg';
        Storage::disk('public')->put($templatePath, base64_decode(self::ONE_PIXEL_JPEG));

        return CertificateAward::create(array_merge([
            'student_id' => $student->id,
            'award_title' => 'Certificate',
            'awarded_date' => now()->toDateString(),
            'template_file' => $templatePath,
            'canvas_data' => $this->canvasData($student->id, $adviser->id),
            'signed_by' => $adviser->name,
            'status' => 'Active',
        ], $overrides));
    }

    private function signedViewUrl(CertificateAward $award): string
    {
        return URL::temporarySignedRoute('public.certificate.view', now()->addDay(), ['award' => $award->id]);
    }

    public function test_public_student_details_links_signed_viewer_not_storage(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $award = $this->awardCertificate($admin, $student, $adviser);

        $response = $this->get(route('landing.student.details', $student->lrn));

        $response->assertOk();
        $response->assertSee('View Certificate', false);
        $response->assertSee('certificates/' . $award->id . '/view', false);
        $response->assertSee('signature=', false);
        $response->assertDontSee('storage/certificate-awards', false);
        $response->assertDontSee('asset(\'storage/', false);
    }

    public function test_public_viewer_renders_completed_certificate_with_download_and_print(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $award = $this->awardCertificate($admin, $student, $adviser);

        $response = $this->get($this->signedViewUrl($award));

        $response->assertOk();
        $response->assertSee('certificate-embed', false);
        $response->assertSee('Download', false);
        $response->assertSee('Print', false);
        $response->assertSee('certificates/' . $award->id . '/file', false);
        $response->assertSee('certificates/' . $award->id . '/download', false);
        $response->assertDontSee('storage/certificate-awards', false);
    }

    public function test_public_certificate_routes_require_valid_signature(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $award = $this->awardCertificate($admin, $student, $adviser);

        $this->get(route('public.certificate.view', $award))->assertForbidden();
        $this->get(route('public.certificate.file', $award))->assertForbidden();
        $this->get(route('public.certificate.download', $award))->assertForbidden();

        $tampered = preg_replace('/signature=[^&]+/', 'signature=invalid', $this->signedViewUrl($award));
        $this->get($tampered)->assertForbidden();
    }

    public function test_public_viewer_reconstructs_legacy_canvas_award(): void
    {
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $award = $this->legacyCanvasAward($student, $adviser);

        $this->assertNull($award->generated_file_path);

        $response = $this->get($this->signedViewUrl($award));

        $response->assertOk();
        $response->assertSee('certificate-student-name-view', false);
        $response->assertSee('certificate-adviser-view', false);
        $response->assertSee('Kathleen Tabadero');
        $response->assertSee('Maria Santos');
        $response->assertSee('Print', false);
        // No final file exists, so no download control is offered.
        $response->assertDontSee('viewer-btn viewer-btn-download', false);
    }

    public function test_public_download_returns_completed_file_with_descriptive_name(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $award = $this->awardCertificate($admin, $student, $adviser);

        $url = URL::temporarySignedRoute('public.certificate.download', now()->addDay(), ['award' => $award->id]);
        $response = $this->get($url);

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('Certificate-', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->streamedContent());
    }

    public function test_public_download_of_legacy_canvas_only_redirects_to_viewer(): void
    {
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $award = $this->legacyCanvasAward($student, $adviser);

        $url = URL::temporarySignedRoute('public.certificate.download', now()->addDay(), ['award' => $award->id]);
        $response = $this->get($url);

        $response->assertRedirect();
        $this->assertStringContainsString('/certificates/' . $award->id . '/view', (string) $response->headers->get('location'));
    }

    public function test_public_access_denied_for_archived_award_or_archived_student(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $award = $this->awardCertificate($admin, $student, $adviser);

        $award->update(['status' => 'Archived']);
        $this->get($this->signedViewUrl($award))->assertNotFound();

        $award->update(['status' => 'Active']);
        $student->update(['status' => 'Archived']);
        $this->get($this->signedViewUrl($award))->assertNotFound();
    }

    public function test_landing_shows_unavailable_message_without_link_when_no_certificate_files(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();

        CertificateAward::create([
            'student_id' => $student->id,
            'award_title' => 'Hollow Award',
            'awarded_date' => now()->toDateString(),
            'status' => 'Active',
            'issued_by' => $admin->id,
        ]);

        $response = $this->get(route('landing.student.details', $student->lrn));

        $response->assertOk();
        $response->assertSee('Completed certificate unavailable', false);
        $response->assertDontSee('View Certificate', false);
    }

    public function test_teacher_of_assigned_student_can_access_admin_created_certificate(): void
    {
        $admin = $this->createAdmin();
        $teacherA = $this->createTeacher();
        $adviser = $this->createTeacher();
        $student = $this->createStudent($teacherA);
        $award = $this->awardCertificate($admin, $student, $adviser);

        $this->assertNull($award->teacher_id);

        $overview = $this->actingAs($teacherA)->getJson(route('admin.students.overview', $student));
        $overview->assertOk();
        $overview->assertJsonPath('certificates.0.view_url', route('admin.certificate.download', $award));
        $overview->assertJsonPath('certificates.0.print_url', route('admin.certificate.print', $award));

        $this->actingAs($teacherA)->get(route('admin.certificate.print', $award))->assertOk();
        $this->actingAs($teacherA)->get(route('admin.certificate.file', $award))->assertOk();

        $download = $this->actingAs($teacherA)->get(route('admin.certificate.download', $award));
        $download->assertOk();
        $this->assertStringContainsString('Certificate-', (string) $download->headers->get('content-disposition'));
    }

    public function test_teacher_without_assignment_is_forbidden_from_certificate_routes(): void
    {
        $admin = $this->createAdmin();
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $adviser = $this->createTeacher();
        $student = $this->createStudent($teacherA);
        $award = $this->awardCertificate($admin, $student, $adviser);

        $this->actingAs($teacherB)->get(route('admin.certificate.print', $award))->assertForbidden();
        $this->actingAs($teacherB)->get(route('admin.certificate.file', $award))->assertForbidden();
        $this->actingAs($teacherB)->get(route('admin.certificate.download', $award))->assertForbidden();

        $overview = $this->actingAs($teacherB)->getJson(route('admin.students.overview', $student));
        $overview->assertForbidden();
    }

    public function test_super_admin_can_view_download_and_print_any_certificate(): void
    {
        $admin = $this->createAdmin();
        $superAdmin = $this->createSuperAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $award = $this->awardCertificate($admin, $student, $adviser);

        $this->actingAs($superAdmin)->get(route('admin.certificate.print', $award))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.certificate.file', $award))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.certificate.download', $award))->assertOk();
    }

    public function test_file_route_never_serves_bare_template_without_canvas_or_final_file(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();

        $templatePath = 'certificate-templates/blank.jpg';
        Storage::disk('public')->put($templatePath, base64_decode(self::ONE_PIXEL_JPEG));
        $award = CertificateAward::create([
            'student_id' => $student->id,
            'award_title' => 'Blank Only',
            'awarded_date' => now()->toDateString(),
            'template_file' => $templatePath,
            'status' => 'Active',
            'issued_by' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('admin.certificate.file', $award))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.certificate.download', $award))->assertNotFound();
    }

    public function test_student_awards_page_shows_print_always_and_download_only_with_final_file(): void
    {
        $teacher = $this->createTeacher();
        $adviser = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $legacy = $this->legacyCanvasAward($student, $adviser);

        $legacyPage = $this->actingAs($teacher)->get(route('students.awards', $student));
        $legacyPage->assertOk();
        $legacyPage->assertSee('Print', false);
        $legacyPage->assertDontSee('btn-achievement', false);
        $legacyPage->assertSee('/admin/certificate/' . $legacy->id . '/print', false);

        $withFinal = $this->awardCertificate($this->createAdmin(), $student, $adviser);

        $finalPage = $this->actingAs($teacher)->get(route('students.awards', $student));
        $finalPage->assertOk();
        $finalPage->assertSee('Download', false);
        $finalPage->assertSee('/admin/certificate/' . $withFinal->id . '/download', false);
    }

    public function test_print_view_uses_served_file_route_not_storage_urls(): void
    {
        $admin = $this->createAdmin();
        $adviser = $this->createTeacher();
        $student = $this->createStudent();
        $award = $this->awardCertificate($admin, $student, $adviser);

        $print = $this->actingAs($admin)->get(route('admin.certificate.print', $award));

        $print->assertOk();
        $print->assertSee(route('admin.certificate.file', $award), false);
        $print->assertDontSee('storage/certificate-awards', false);
        $print->assertDontSee("asset('storage/", false);
    }

    public function test_public_viewer_shows_unavailable_message_for_hollow_award(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();

        $award = CertificateAward::create([
            'student_id' => $student->id,
            'award_title' => 'Hollow Award',
            'awarded_date' => now()->toDateString(),
            'status' => 'Active',
            'issued_by' => $admin->id,
        ]);

        $response = $this->get($this->signedViewUrl($award));

        $response->assertOk();
        $response->assertSee('Completed certificate unavailable', false);
        $response->assertDontSee('viewer-btn viewer-btn-download', false);
        $response->assertDontSee('viewer-btn viewer-btn-print', false);
    }
}
