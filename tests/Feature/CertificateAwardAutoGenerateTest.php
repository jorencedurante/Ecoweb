<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\CertificateAward;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateAwardAutoGenerateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_admin_sees_certificate_with_monitoring_counter_and_no_quest_management(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.certificate'));

        $response->assertOk();
        $response->assertSee('Auto Generated Certificate', false);
        $response->assertDontSee('Certificate / Award History', false);
        $response->assertDontSee('Design Certificate', false);
        $response->assertSee('Search and Select Student', false);
        $response->assertSee('Select Teacher / Adviser', false);
        $response->assertSee('adviserSelect', false);
        // Admin is view-only: no quest management controls.
        $response->assertDontSee('Add Achievement Quest', false);
        $response->assertSee('Monitoring', false);

        for ($i = 1; $i <= 5; $i++) {
            Achievement::create([
                'title' => 'Quest ' . $i,
                'milestone' => 'Points',
                'points_required' => 10,
                'status' => 'Active',
                'created_by' => $admin->id,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.certificate'));

        $response->assertOk();
        $response->assertSee('Monitoring', false);
        $response->assertDontSee('Maximum of 5 achievement quests reached', false);
        $response->assertDontSee('>+ Add Achievement Quest<', false);
    }

    public function test_no_teacher_or_student_is_auto_selected(): void
    {
        $admin = $this->createAdmin();
        $this->createTeacher();

        $response = $this->actingAs($admin)->get(route('admin.certificate'));

        $response->assertOk();

        // The placeholder option is the only one selected.
        $this->assertMatchesRegularExpression(
            '/<option value="" disabled selected>Select Teacher \/ Adviser<\/option>/',
            $response->getContent()
        );

        preg_match_all('/<option value="(\d+)"[^>]*\sselected>/', $response->getContent(), $matches);
        $this->assertSame([], $matches[1], 'No teacher option should be auto-selected on a fresh page load.');

        $this->assertMatchesRegularExpression(
            '/id="student_id"[^>]*value=""/',
            $response->getContent()
        );
    }

    public function test_certificate_award_hides_placement_controls_and_save_button(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.certificate'));

        $response->assertOk();
        $response->assertSee('Print Preview', false);
        $response->assertDontSee('Save Certificate', false);
        $response->assertDontSee('Fixed Text Placement', false);
        $response->assertDontSee('Vertical Position', false);
        $response->assertDontSee('Name Box Width', false);
        $response->assertDontSee('Font Family', false);
        $response->assertSee('certificate-name-overlay', false);
        $response->assertSee('certificate-adviser-overlay', false);
        $response->assertSee('width: 30%', false);
    }

    public function test_font_settings_replaced_description_field(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.certificate'));

        $response->assertOk();
        $response->assertDontSee('Description (optional)', false);
        $response->assertDontSee('award_description', false);
        $response->assertSee('Font Settings', false);
        $response->assertSee('id="certFontFamily"', false);
        $response->assertSee('id="studentFontSize"', false);
        $response->assertSee('id="adviserFontSize"', false);
        $response->assertDontSee('id="certFontSize"', false);
        $response->assertDontSee('id="stuFontFamily"', false);
        $response->assertDontSee('id="advFontFamily"', false);
        $response->assertSee('Font Style', false);
        $response->assertSee('Student Name Size', false);
        $response->assertSee('Adviser Name Size', false);
        self::assertSame(1, substr_count($response->getContent(), 'Font Style'));
        self::assertSame(1, substr_count($response->getContent(), 'id="fontSettings"'));
        $response->assertSeeInOrder(['Select Teacher / Adviser', 'Font Settings', 'Print Preview']);
        $response->assertSee('Garamond', false);
        $response->assertSee('Print Preview', false);
    }

    public function test_optional_drag_positioning_and_reset_control(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.certificate'));

        $response->assertOk();
        // reset button sits below Font Settings in the left column
        $response->assertSee('id="resetNamePositionsBtn"', false);
        $response->assertSee('Reset Name Positions', false);
        $response->assertSeeInOrder(['Font Settings', 'Reset Name Positions']);
        // pointer-event drag plumbing on the existing overlays
        $response->assertSee('setPointerCapture', false);
        $response->assertSee('pointercancel', false);
        $response->assertSee('studentPosOverride', false);
        $response->assertSee('adviserPosOverride', false);
        $response->assertSee('effectiveCfg', false);
        // default automatic alignment stays the source position
        $response->assertSee('centerX: studentLineCenterX', false);
        $response->assertSee('centerX: adviserLineCenterX', false);
        // hidden until hover/active so the preview stays clean
        $response->assertSee('touch-action: none', false);
        $response->assertSee('cursor: move', false);
    }

    public function test_obsolete_create_route_redirects_to_certificate_award(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.certificate.create'));

        $response->assertRedirect(route('admin.certificate'));
    }

    public function test_backend_blocks_sixth_quest_per_teacher(): void
    {
        $teacher = $this->createTeacher();

        for ($i = 1; $i <= 5; $i++) {
            Achievement::create([
                'title' => 'Existing ' . $i,
                'milestone' => 'Points',
                'points_required' => 5,
                'status' => 'Active',
                'created_by' => $teacher->id,
            ]);
        }

        $response = $this->actingAs($teacher)->post(route('achievement-quests.store'), [
            'title' => 'Sixth Quest',
            'requirement_type' => 'points',
            'required_value' => 10,
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('admin.certificate'));
        $response->assertSessionHasErrors('title');
        $this->assertSame(5, Achievement::whereNull('student_id')->where('created_by', $teacher->id)->count());
        $this->assertDatabaseMissing('achievements', ['title' => 'Sixth Quest']);
    }

    public function test_save_canvas_stores_adviser(): void
    {
        $teacher = $this->createTeacher();
        $adviser = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->post(route('admin.certificate.save-canvas'), [
            'student_id' => $student->id,
            'adviser_id' => $adviser->id,
            'award_title' => 'With Adviser',
            'template_file' => UploadedFile::fake()->create('t.jpg', 10, 'image/jpeg'),
            'canvas_data' => json_encode([
                [
                    'type' => 'adviser_name',
                    'teacher_id' => $adviser->id,
                    'text' => $adviser->name,
                    'x' => 72, 'y' => 87, 'width' => 34,
                    'fontSize' => 26, 'fontSizePref' => 26, 'fontSizeMin' => 10,
                    'fontFamily' => 'Georgia', 'color' => '#1F2937', 'fontWeight' => '600',
                    'textAlign' => 'center',
                ],
            ]),
        ]);

        $response->assertRedirect(route('admin.certificate'));

        $award = CertificateAward::where('award_title', 'With Adviser')->first();
        $this->assertNotNull($award);
        $this->assertSame($adviser->name, $award->signed_by);

        $print = $this->actingAs($teacher)->get(route('admin.certificate.print', $award->id));
        $print->assertOk();
        $print->assertSee('certificate-adviser-print', false);
        $print->assertSee($adviser->name);
    }
}
