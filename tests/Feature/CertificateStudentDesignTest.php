<?php

namespace Tests\Feature;

use App\Models\CertificateAward;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateStudentDesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function fakeTemplate(): UploadedFile
    {
        return UploadedFile::fake()->create('template.jpg', 100, 'image/jpeg');
    }

    private function nameBoxCanvasData(string $name): string
    {
        return json_encode([
            [
                'type' => 'student_name',
                'text' => $name,
                'x' => 50,
                'y' => 45,
                'width' => 70,
                'fontSize' => 36,
                'fontSizePref' => 40,
                'fontSizeMin' => 16,
                'fontFamily' => 'Georgia',
                'color' => '#1F2937',
                'fontWeight' => '700',
                'textAlign' => 'center',
            ],
        ]);
    }

    public function test_obsolete_design_certificate_page_redirects_to_certificate_award(): void
    {
        $teacher = $this->createTeacher();

        $response = $this->actingAs($teacher)->get(route('admin.certificate.create'));

        $response->assertRedirect(route('admin.certificate'));
    }

    public function test_teacher_student_search_returns_only_own_class_students(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $this->createStudent($teacherA, [
            'first_name' => 'Maria',
            'middle_name' => 'L.',
            'last_name' => 'Castillo',
            'full_name' => 'Maria L. Castillo',
            'grade_level' => 'Grade 6',
        ]);
        $this->createStudent($teacherB, [
            'first_name' => 'Marco',
            'last_name' => 'Castillo',
            'full_name' => 'Marco Castillo',
            'grade_level' => 'Grade 2',
        ]);

        $own = $this->actingAs($teacherA)
            ->getJson(route('admin.students.search', ['q' => 'Cast']))
            ->json();

        $this->assertCount(1, $own);
        $this->assertSame('Maria L. Castillo', $own[0]['name']);
        $this->assertSame('Grade 6', $own[0]['grade_level']);
        $this->assertNotEmpty($own[0]['lrn']);

        $other = $this->actingAs($teacherB)
            ->getJson(route('admin.students.search', ['q' => 'Maria']))
            ->json();

        $this->assertCount(0, $other);
    }

    public function test_teacher_student_search_matches_lrn_case_insensitively(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, [
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'full_name' => 'Ana Cruz',
        ]);

        $partial = $this->actingAs($teacher)
            ->getJson(route('admin.students.search', ['q' => substr($student->lrn, 0, 4)]))
            ->json();

        $this->assertCount(1, $partial);
        $this->assertSame($student->id, $partial[0]['id']);
    }

    public function test_admin_student_search_sees_all_students(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $this->createStudent($teacherA, ['first_name' => 'Maria', 'last_name' => 'Santos', 'full_name' => 'Maria Santos']);
        $this->createStudent($teacherB, ['first_name' => 'Mark', 'last_name' => 'Aquino', 'full_name' => 'Mark Aquino']);

        $admin = $this->createAdmin();

        $results = $this->actingAs($admin)
            ->getJson(route('admin.students.search', ['q' => 'a']))
            ->json();

        $names = array_column($results, 'name');
        $this->assertContains('Maria Santos', $names);
        $this->assertContains('Mark Aquino', $names);
    }

    public function test_save_canvas_stores_student_and_student_name_box(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, [
            'first_name' => 'Maria Alexandra Villanueva',
            'last_name' => 'Castillo',
            'full_name' => 'Maria Alexandra Villanueva Castillo',
        ]);

        $response = $this->actingAs($teacher)->post(route('admin.certificate.save-canvas'), [
            'student_id' => $student->id,
            'award_title' => 'Certificate of Recognition',
            'template_file' => $this->fakeTemplate(),
            'canvas_data' => $this->nameBoxCanvasData('Maria Alexandra Villanueva Castillo'),
        ]);

        $response->assertRedirect(route('admin.certificate'));

        $award = CertificateAward::where('student_id', $student->id)->first();
        $this->assertNotNull($award);
        $this->assertSame('Active', $award->status);
        $this->assertNotNull($award->template_file);

        $nameEntry = collect($award->canvas_data)->firstWhere('type', 'student_name');
        $this->assertNotNull($nameEntry);
        $this->assertSame('Maria Alexandra Villanueva Castillo', $nameEntry['text']);
        $this->assertSame(70, (int) $nameEntry['width']);
        $this->assertSame('Georgia', $nameEntry['fontFamily']);
        $this->assertSame(16, (int) $nameEntry['fontSizeMin']);
    }

    public function test_teacher_cannot_save_canvas_for_another_teachers_student(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $studentOfA = $this->createStudent($teacherA);

        $response = $this->actingAs($teacherB)->post(route('admin.certificate.save-canvas'), [
            'student_id' => $studentOfA->id,
            'award_title' => 'Not Allowed',
            'template_file' => $this->fakeTemplate(),
            'canvas_data' => $this->nameBoxCanvasData('Someone Else'),
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('certificate_awards', ['award_title' => 'Not Allowed']);
    }

    public function test_update_canvas_updates_student_without_duplicating_record(): void
    {
        $teacher = $this->createTeacher();
        $studentOne = $this->createStudent($teacher, [
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'full_name' => 'Ana Cruz',
        ]);
        $studentTwo = $this->createStudent($teacher, [
            'first_name' => 'Maria L.',
            'last_name' => 'Castillo',
            'full_name' => 'Maria L. Castillo',
        ]);

        $this->actingAs($teacher)->post(route('admin.certificate.save-canvas'), [
            'student_id' => $studentOne->id,
            'award_title' => 'Best Collector',
            'template_file' => $this->fakeTemplate(),
            'canvas_data' => $this->nameBoxCanvasData('Ana Cruz'),
        ])->assertRedirect(route('admin.certificate'));

        $award = CertificateAward::first();
        $this->assertNotNull($award);

        $response = $this->actingAs($teacher)->put(
            route('admin.certificate.update-canvas', $award->id),
            [
                'student_id' => $studentTwo->id,
                'award_title' => 'Best Collector',
                'canvas_data' => $this->nameBoxCanvasData('Maria L. Castillo'),
            ]
        );

        $response->assertRedirect(route('admin.certificate'));
        $this->assertSame(1, CertificateAward::count());

        $award->refresh();
        $this->assertSame($studentTwo->id, $award->student_id);

        $nameEntry = collect($award->canvas_data)->firstWhere('type', 'student_name');
        $this->assertSame('Maria L. Castillo', $nameEntry['text']);
    }

    public function test_print_page_renders_student_name_box_without_editing_borders(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, [
            'first_name' => 'Juan Miguel Alexander Santiago',
            'last_name' => 'Dela Cruz Villanueva',
            'full_name' => 'Juan Miguel Alexander Santiago Dela Cruz Villanueva',
        ]);

        $this->actingAs($teacher)->post(route('admin.certificate.save-canvas'), [
            'student_id' => $student->id,
            'award_title' => 'Long Name Certificate',
            'template_file' => $this->fakeTemplate(),
            'canvas_data' => $this->nameBoxCanvasData('Juan Miguel Alexander Santiago Dela Cruz Villanueva'),
        ])->assertRedirect(route('admin.certificate'));

        $award = CertificateAward::first();

        $response = $this->actingAs($teacher)->get(route('admin.certificate.print', $award->id));

        $response->assertOk();
        $response->assertSee('certificate-student-name-print', false);
        $response->assertSee('Juan Miguel Alexander Santiago Dela Cruz Villanueva');
        $response->assertSee('data-width="70"', false);
        $response->assertDontSee('border: 2px dashed', false);
    }
}
