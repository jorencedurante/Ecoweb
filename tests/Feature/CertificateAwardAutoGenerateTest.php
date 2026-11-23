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

        $response = $this->actingAs($admin)->get(route(portal_prefix() . '.certificate'));

        $response->assertOk();
        $response->assertSee('Auto Generated Certificate', false);
        $response->assertDontSee('Certificate / Award History', false);
        $response->assertDontSee('Design Certificate', false);
        $response->assertSee('Search and Select Student', false);
        $response->assertSee('Select Adviser', false);
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

        $response = $this->actingAs($admin)->get(route(portal_prefix() . '.certificate'));

        $response->assertOk();
        $response->assertSee('Monitoring', false);
        $response->assertDontSee('Maximum of 5 achievement quests reached', false);
        $response->assertDontSee('>+ Add Achievement Quest<', false);
    }

    public function test_no_teacher_or_student_is_auto_selected(): void
    {
        $admin = $this->createAdmin();
        $this->createTeacher();

        $response = $this->actingAs($admin)->get(route(portal_prefix() . '.certificate'));

        $response->assertOk();

        // The placeholder option is the only one selected.
        $this->assertMatchesRegularExpression(
            '/<option value="" disabled selected>Select Adviser<\/option>/',
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

        $response = $this->actingAs($admin)->get(route(portal_prefix() . '.certificate'));

        $response->assertOk();
        $response->assertSee('Print Preview', false);
        $response->assertDontSee('Save Certificate', false);
        $response->assertDontSee('Fixed Text Placement', false);
        $response->assertDontSee('Vertical Position', false);
        $response->assertDontSee('Name Box Width', false);
        $response->assertDontSee('Font Family', false);
        $response->assertSee('certificate-name-overlay', false);
        $response->assertSee('certificate-adviser-overlay', false);
        $response->assertSee('width: 29.65%', false);
    }

    public function test_font_settings_replaced_description_field(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route(portal_prefix() . '.certificate'));

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
        $response->assertSeeInOrder(['Select Adviser', 'Font Settings', 'Print Preview']);
        $response->assertSee('Garamond', false);
        $response->assertSee('Print Preview', false);
    }

    public function test_name_overlays_are_automatic_and_not_user_positioned(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route(portal_prefix() . '.certificate'));

        $response->assertOk();
        $source = (string) $response->getContent();

        $this->assertStringContainsString('centerX: studentLineCenterX', $source);
        $this->assertStringContainsString('centerX: adviserLineCenterX', $source);
        $this->assertStringContainsString('effectiveCfg', $source);
        $this->assertStringContainsString('runAutomaticAlignment()', $source);

        $this->assertStringNotContainsString('setPointerCapture', $source);
        $this->assertStringNotContainsString('touch-action: none', $source);
        $this->assertStringNotContainsString('cursor: move', $source);
        $this->assertStringNotContainsString('Reset Name Positions', $source);
    }

    public function test_automatic_text_positions_are_calibrated_to_current_template(): void
    {
        $source = (string) file_get_contents(resource_path('views/pages/certificate.blade.php'));

        // Anchors measured from the current 1366x768 certificate template.
        foreach ([
            'var studentLineCenterX = 51.65;',
            'var studentLineY = 64.32;',
            'var studentBoxWidth = 29.65;',
            'var adviserLineCenterX = 26.13;',
            'var adviserLineY = 92.32;',
            'var adviserBoxWidth = 13.10;',
            'var principalLineCenterX = 73.21;',
            'var principalLineY = 92.32;',
            'var principalBoxWidth = 15.80;',
        ] as $constant) {
            $this->assertStringContainsString($constant, $source, $constant);
        }

        // The matching CSS overlay defaults must agree with the JS config.
        foreach (['width: 29.65%', 'left: 51.65%', 'width: 13.10%', 'left: 73.21%'] as $style) {
            $this->assertStringContainsString($style, $source, $style);
        }

        // Uploading a new template clears every drag override, including the
        // Principal one, so no old position survives the template change.
        $uploadBlock = substr($source, strpos($source, 'A new template always gets fresh automatic positions'), 500);
        $this->assertStringContainsString('studentPosOverride = null;', $uploadBlock);
        $this->assertStringContainsString('adviserPosOverride = null;', $uploadBlock);
        $this->assertStringContainsString('principalPosOverride = null;', $uploadBlock);

        $response = $this->actingAs($this->createAdmin())->get(route(portal_prefix() . '.certificate'));
        $response->assertOk()->assertSee('Automatic', false);
    }

    public function test_obsolete_create_route_redirects_to_certificate_award(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route(portal_prefix() . '.certificate.create'));

        $response->assertRedirect(route(portal_prefix() . '.certificate'));
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

        $response = $this->actingAs($teacher)->post(route('teacher.achievement-quests.store'), [
            'title' => 'Sixth Quest',
            'requirement_type' => 'points',
            'required_value' => 10,
            'status' => 'Active',
        ]);

        $response->assertRedirect(route(portal_prefix() . '.certificate'));
        $response->assertSessionHasErrors('title');
        $this->assertSame(5, Achievement::whereNull('student_id')->where('created_by', $teacher->id)->count());
        $this->assertDatabaseMissing('achievements', ['title' => 'Sixth Quest']);
    }

    public function test_save_canvas_stores_adviser(): void
    {
        $teacher = $this->createTeacher();
        $teacher->forceFill(['can_print_certificates' => true])->save();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->post(route(portal_prefix() . '.certificate.save-canvas'), [
            'student_id' => $student->id,
            'adviser_id' => $teacher->id,
            'award_title' => 'With Adviser',
            'template_file' => UploadedFile::fake()->create('t.jpg', 10, 'image/jpeg'),
            'canvas_data' => json_encode([
                [
                    'type' => 'adviser_name',
                    'teacher_id' => $teacher->id,
                    'text' => $teacher->name,
                    'x' => 72, 'y' => 87, 'width' => 34,
                    'fontSize' => 26, 'fontSizePref' => 26, 'fontSizeMin' => 10,
                    'fontFamily' => 'Georgia', 'color' => '#1F2937', 'fontWeight' => '600',
                    'textAlign' => 'center',
                ],
            ]),
        ]);

        $response->assertRedirect(route(portal_prefix() . '.certificate'));

        $award = CertificateAward::where('award_title', 'With Adviser')->first();
        $this->assertNotNull($award);
        $this->assertSame($teacher->name, $award->signed_by);

        $print = $this->actingAs($teacher)->get(route(portal_prefix() . '.certificate.print', $award->id));
        $print->assertOk();
        $print->assertSee('certificate-adviser-print', false);
        $print->assertSee($teacher->name);
    }
}
