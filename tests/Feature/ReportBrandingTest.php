<?php

namespace Tests\Feature;

use App\Models\BottleCollection;
use App\Models\Student;
use Tests\TestCase;

class ReportBrandingTest extends TestCase
{
    private const LOGO_PATHS = [
        'images/school-logo.jpeg.png',
        'images/district-logo.jpeg.png',
        'images/division-logo.jpeg.png',
        'images/deped-logo.jpeg.png',
    ];

    public function test_logo_assets_exist_on_disk(): void
    {
        foreach (self::LOGO_PATHS as $path) {
            $this->assertFileExists(public_path($path), $path);
        }
    }

    public function test_bottle_collection_report_header_is_branded(): void
    {
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent($this->createTeacher());

        BottleCollection::create([
            'student_id' => $student->id,
            'bottle_count' => 2,
            'collection_date' => now()->toDateString(),
        ]);

        $html = (string) $this->actingAs($admin)
            ->get(route('admin.reports.bottle-collection.print'))
            ->assertOk()->getContent();

        $this->assertStringContainsString('San Narciso Elementary School', $html);
        $this->assertStringContainsString('Bottle Collection Report', $html);
        $this->assertStringContainsString('EcoCollect Environmental Achievement Tracking System', $html);
        foreach (self::LOGO_PATHS as $path) {
            $this->assertStringContainsString(asset($path), $html);
        }
        $this->assertStringContainsString('official-logos-left', $html);
        $this->assertStringContainsString('official-logos-right', $html);
        // Body content remains intact.
        $this->assertStringContainsString('Total Bottles', $html);
        $this->assertStringContainsString('EcoCollect Report', $html);
    }

    public function test_all_report_print_views_use_shared_branded_header(): void
    {
        $files = [
            'bottle-collection.blade.php',
            'item-claims.blade.php',
            'summary-item.blade.php',
            'student-report.blade.php',
            'top-students.blade.php',
            'student-activities.blade.php',
            'student-performance.blade.php',
        ];

        foreach ($files as $file) {
            $source = (string) file_get_contents(resource_path('views/pages/reports/print/' . $file));
            $this->assertStringContainsString("@include('partials.reports.print-header'", $source, $file);
            $this->assertStringContainsString('<div class="report-header">', $source, $file);
        }

        $header = (string) file_get_contents(resource_path('views/partials/reports/print-header.blade.php'));
        foreach (self::LOGO_PATHS as $path) {
            $this->assertStringContainsString("asset('" . $path . "')", $header);
        }
        $this->assertStringContainsString('San Narciso Elementary School', $header);
        $this->assertStringContainsString('object-fit: contain', $header);
        $this->assertStringContainsString('@supports (object-fit: cover)', $header);
        $this->assertStringContainsString('width: 78px', $header);
        $this->assertStringContainsString('flex: 0 0 auto', $header);
        $this->assertStringContainsString('max-width: 960px', $header);
        $this->assertStringNotContainsString('space-between', $header);
        $this->assertStringNotContainsString('flex: 1 1 auto', $header);

        // Certificate print views must remain untouched.
        foreach (['certificate-print.blade.php', 'certificate-viewer.blade.php'] as $file) {
            $source = (string) file_get_contents(resource_path('views/pages/' . $file));
            $this->assertStringNotContainsString('partials.reports.print-header', $source, $file);
        }
    }

    public function test_unassigned_sub_teacher_report_module_still_returns_notice(): void
    {
        $subTeacher = $this->createAccount([
            'role' => 'sub_teacher', 'username' => 'report-brand-sub', 'status' => 'active',
        ]);

        $this->actingAs($subTeacher)->get(route('teacher.reports'))
            ->assertOk()
            ->assertSee('No Teacher Assignment Yet')
            ->assertDontSee('San Narciso Elementary School');
    }
}
