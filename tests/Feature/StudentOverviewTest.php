<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\BottleCollection;
use App\Models\CertificateAward;
use App\Models\StudentAchievement;
use Tests\TestCase;

class StudentOverviewTest extends TestCase
{
    public function test_overview_endpoint_returns_summary_totals_for_super_admin(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, ['total_points' => 9]);

        foreach ([[3, 2], [4, 3]] as [$count, $points]) {
            BottleCollection::create([
                'student_id' => $student->id,
                'lrn' => $student->lrn,
                'collection_date' => now()->toDateString(),
                'collection_time' => '08:30',
                'bottle_count' => $count,
                'points_earned' => $points,
                'created_by' => $teacher->id,
            ]);
        }

        $response = $this->actingAs($superAdmin)->getJson(route('admin.students.overview', $student->id));

        $response->assertOk()->assertJsonStructure([
            'student' => ['id', 'full_name', 'lrn', 'initials', 'details_url', 'section', 'school_year', 'house_street', 'guardian_relationship'],
            'summary' => ['total_bottles', 'total_points', 'points_earned', 'collections_count', 'achievements', 'certificates'],
            'recent_activity',
            'achievement_progress',
            'recent_collections',
            'collections',
            'achievements' => ['earned', 'progress'],
            'certificates',
            'meta',
        ]);

        // Total bottles must be the sum of Scan Bottle bottle_count values,
        // not the number of collection records.
        $this->assertSame(7, $response->json('summary.total_bottles'));
        $this->assertSame(2, count($response->json('collections')));
        // Points always come from the stored IoT-calculated total.
        $this->assertSame(9, $response->json('summary.total_points'));
        $this->assertSame($student->id, $response->json('student.id'));
        // Collection count is the number of records; points_earned is their sum.
        $this->assertSame(2, $response->json('summary.collections_count'));
        $this->assertSame(5, $response->json('summary.points_earned'));
        // bottle_collections has no status column, so status is a derived display label.
        $this->assertSame('Recorded', $response->json('collections.0.status'));
    }

    public function test_overview_includes_achievement_progress_and_certificates(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, ['total_points' => 4]);

        $quest = Achievement::create([
            'title' => 'Eco Hero',
            'description' => 'Collect ten bottles',
            'badge_name' => 'Eco Hero Badge',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 10,
            'points_required' => 0,
        ]);

        StudentAchievement::create([
            'student_id' => $student->id,
            'achievement_quest_id' => $quest->id,
            'awarded_date' => now()->toDateString(),
        ]);

        CertificateAward::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'award_title' => 'Best Collector',
            'award_description' => 'Top bottle collector',
            'awarded_date' => now()->toDateString(),
            'certificate_file' => 'certificates/best.pdf',
            'status' => 'Active',
            'issued_by' => $teacher->id,
        ]);

        $response = $this->actingAs($teacher)->getJson(route('admin.students.overview', $student->id));

        $response->assertOk();
        $this->assertSame(1, $response->json('summary.achievements'));
        $this->assertSame(1, $response->json('summary.certificates'));
        $this->assertSame('Eco Hero', $response->json('achievements.earned.0.title'));
        $this->assertSame('Best Collector', $response->json('certificates.0.title'));
        $this->assertNotNull($response->json('achievements.progress.0'));
        $this->assertSame('bottles', $response->json('achievements.progress.0.metric'));
    }

    public function test_teacher_can_view_own_students_overview(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->getJson(route('admin.students.overview', $student->id));

        $response->assertOk();
        $this->assertSame($student->id, $response->json('student.id'));
    }

    public function test_teacher_cannot_view_another_teachers_student_overview(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $studentOfA = $this->createStudent($teacherA);

        $response = $this->actingAs($teacherB)->getJson(route('admin.students.overview', $studentOfA->id));

        $response->assertForbidden();
    }

    public function test_view_only_admin_account_can_read_overview(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($admin)->getJson(route('admin.students.overview', $student->id));

        $response->assertOk();
    }

    public function test_guest_is_redirected_from_overview(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->get(route('admin.students.overview', $student->id));

        $response->assertRedirect();
    }

    public function test_students_page_shows_toolbar_and_overview_modal(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->get(route('admin.students'));

        $response->assertOk()
            ->assertSee('studentOverviewModal', false)
            ->assertSee('data-overview-open', false)
            ->assertSee('Search by name, LRN, or student ID...', false)
            ->assertSee('id="stuSearchInput"', false)
            ->assertSee('role="tablist"', false)
            ->assertSee(route('admin.students.overview', $student->id), false);
    }

    public function test_students_page_shows_active_filter_chips_and_result_count(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->createStudent($superAdmin, ['first_name' => 'Chipper', 'last_name' => 'Student', 'gender' => 'Male']);

        $response = $this->actingAs($superAdmin)->get(route('admin.students', [
            'search' => 'Chipper',
            'gender' => 'Male',
        ]));

        $response->assertOk()
            ->assertSee('Active filters', false)
            ->assertSee('stu-chip', false)
            ->assertSee('Clear all', false)
            ->assertSee('Chipper', false)
            ->assertSee('Showing 1 student matching your filters', false);
    }

    public function test_students_page_without_filters_has_no_filter_chips(): void
    {
        $teacher = $this->createTeacher();
        $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->get(route('admin.students'));

        $response->assertOk()
            ->assertDontSee('Active filters', false)
            ->assertSee('Showing 1 student', false);
    }

    public function test_overview_returns_full_details_profile_progress_and_activity(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, [
            'total_points' => 4,
            'birth_date' => '2015-05-04',
            'age' => 11,
            'mother_tongue' => 'Tagalog',
            'house_street' => '123 Mabini St',
            'barangay' => 'Poblacion',
            'municipality_city' => 'San Pablo',
            'province' => 'Laguna',
            'father_name' => 'Juan Dela Cruz',
            'mother_maiden_name' => 'Maria Santos',
            'guardian_name' => 'Maria Santos',
            'guardian_relationship' => 'Mother',
            'contact_number' => '09171234567',
            'learning_modality' => 'Modular',
            'remarks' => 'With honors',
        ]);
        $student->enrollments()->update([
            'section' => 'Sampaguita',
            'school_year' => '2026-2027',
        ]);

        BottleCollection::create([
            'student_id' => $student->id,
            'lrn' => $student->lrn,
            'collection_date' => now()->toDateString(),
            'collection_time' => '08:30',
            'bottle_count' => 5,
            'points_earned' => 3,
            'created_by' => $teacher->id,
        ]);

        Achievement::create([
            'title' => 'Eco Hero',
            'description' => 'Collect ten bottles',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 10,
            'points_required' => 0,
        ]);

        $response = $this->actingAs($teacher)->getJson(route('admin.students.overview', $student->id));

        $response->assertOk();

        // Full Details profile fields (same data source as the Student Details page).
        $this->assertSame('123 Mabini St', $response->json('student.house_street'));
        $this->assertSame('Poblacion', $response->json('student.barangay'));
        $this->assertSame('Sampaguita', $response->json('student.section'));
        $this->assertSame('2026-2027', $response->json('student.school_year'));
        $this->assertSame('Mother', $response->json('student.guardian_relationship'));
        $this->assertSame(11, $response->json('student.age'));

        // Collection summary from existing stored data only.
        $this->assertSame(5, $response->json('summary.total_bottles'));
        $this->assertSame(1, $response->json('summary.collections_count'));
        $this->assertSame(3, $response->json('summary.points_earned'));
        $this->assertSame(4, $response->json('summary.total_points'));

        // Achievement progress derived from the same quest math as the achievements page.
        $this->assertSame(5, $response->json('achievement_progress.current_bottles'));
        $this->assertSame('Eco Hero', $response->json('achievement_progress.next_goal.title'));
        $this->assertSame(5, $response->json('achievement_progress.remaining'));
        $this->assertSame(50, $response->json('achievement_progress.progress'));
        $this->assertSame(50, $response->json('achievements.progress.0.progress'));

        // Recent activity merges collections, achievements, and certificates.
        $this->assertSame('collection', $response->json('recent_activity.0.type'));
        $this->assertSame('5 bottles • 3 points', $response->json('recent_activity.0.detail'));
    }

    public function test_students_page_has_five_tabs_interactive_name_and_no_external_details_link(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->get(route('admin.students'));

        $response->assertOk()
            ->assertSee('data-so-tab="overview"', false)
            ->assertSee('data-so-tab="collections"', false)
            ->assertSee('data-so-tab="achievements"', false)
            ->assertSee('data-so-tab="certificates"', false)
            ->assertSee('data-so-tab="full-details"', false)
            ->assertSee('Full Details', false)
            // Name and avatar open the modal on the Full Details tab.
            ->assertSee('stu-name-btn', false)
            ->assertSee('stu-avatar', false)
            ->assertSee('data-tab="full-details"', false)
            ->assertSee('aria-label="Open full details for ' . e($student->full_name) . '"', false)
            // Action buttons open their specific tabs.
            ->assertSee('data-tab="overview"', false)
            ->assertSee('data-tab="achievements"', false)
            ->assertSee('data-tab="certificates"', false)
            // The external "Open full details" footer link was removed.
            ->assertDontSee('so-details-link', false)
            ->assertDontSee('soDetailsLink', false);
    }

    public function test_students_table_has_colgroup_and_compact_status_cell_for_teacher(): void
    {
        $teacher = $this->createTeacher();
        $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->get(route('admin.students'));

        $response->assertOk()
            ->assertSee('<colgroup>', false)
            ->assertSee('class="stu-col-lrn"', false)
            ->assertSee('class="stu-col-name"', false)
            ->assertSee('class="stu-col-grade"', false)
            ->assertSee('class="stu-col-qr"', false)
            ->assertSee('class="stu-col-points"', false)
            ->assertSee('class="stu-col-status"', false)
            // Teachers see edit/archive, so the actions column uses the 5-button width class.
            ->assertSee('class="stu-col-actions stu-actions-5"', false)
            ->assertSee('class="cell-status"', false);
    }

    public function test_students_table_uses_narrow_actions_col_for_admin(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $this->createStudent($teacher);

        $response = $this->actingAs($admin)->get(route('admin.students'));

        $response->assertOk()
            // Admin is view-only (3 action buttons: view, achievements, certificates).
            ->assertSee('class="stu-col-actions stu-actions-3"', false)
            ->assertDontSee('stu-actions-5', false);
    }
}
