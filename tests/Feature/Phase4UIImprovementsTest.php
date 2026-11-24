<?php

namespace Tests\Feature;

use App\Models\ClaimItem;
use App\Models\StudentClaim;
use Tests\TestCase;

class Phase4UIImprovementsTest extends TestCase
{
    private function item(array $overrides = []): ClaimItem
    {
        return ClaimItem::create(array_merge([
            'item_name' => 'Ballpen',
            'description' => 'Ballpen',
            'points_required' => 10,
            'quantity' => 500,
            'status' => 'Available',
        ], $overrides));
    }

    private function claim($student, ClaimItem $item, array $overrides = []): StudentClaim
    {
        return StudentClaim::create(array_merge([
            'student_id' => $student->id,
            'claim_item_id' => $item->id,
            'quantity' => 1,
            'item_name' => $item->item_name,
            'points_deducted' => 10,
            'points_before' => 100,
            'points_after' => 90,
            'claim_date' => now()->toDateString(),
            'status' => 'Pending',
            'is_reserved' => true,
        ], $overrides));
    }

    public function test_create_account_modal_is_scrollable_with_accessible_footer(): void
    {
        $source = (string) file_get_contents(resource_path('views/pages/teachers.blade.php'));

        $this->assertStringContainsString('max-height:90dvh', $source);
        $this->assertStringContainsString('.create-teacher-modal .modal-body{flex:1 1 auto;min-height:0;overflow-y:auto', $source);
        $this->assertStringContainsString('.create-teacher-modal .modal-footer{flex:0 0 auto;position:sticky;bottom:0', $source);
        $this->assertStringContainsString('id="ctSubTeacherFields"', $source);
        $this->assertStringContainsString('id="createTeacherSubmit"', $source);
    }

    public function test_system_settings_form_is_centered_and_still_saves(): void
    {
        $principal = $this->createSuperAdmin();
        $source = (string) file_get_contents(resource_path('views/pages/settings.blade.php'));

        $this->assertStringContainsString('max-width:600px;margin:0 auto', $source);

        $response = $this->actingAs($principal)->get(route('admin.system-settings'));
        $response->assertOk()->assertSee('admin_name')->assertSee('Save Changes');

        $this->actingAs($principal)->post(route('admin.settings.general'), [
            'admin_name' => 'Center Test Admin',
            'school_organization' => 'Center Test School',
            'address' => 'Center Test Address',
        ])->assertRedirect();

        $this->assertDatabaseHas('system_settings', ['admin_name' => 'Center Test Admin']);
    }

    public function test_claim_details_use_popups_and_compact_rows(): void
    {
        $admin = $this->createSuperAdmin();
        $item = $this->item();
        $student = $this->createStudent(null, ['first_name' => 'Popup', 'last_name' => 'Student']);
        $this->claim($student, $item);

        $response = $this->actingAs($admin)->get(route('admin.claims.index'));
        $response->assertOk()
            ->assertSee('Pending Item Claims')
            ->assertSee('id="claimDetailsModal"', false)
            ->assertSee('claim-details-modal-body', false)
            ->assertSee('claim-section-pagination', false);

        $html = (string) $response->getContent();
        $this->assertStringNotContainsString('class="approved-student-details" style="display:none', $html);
        $this->assertStringContainsString('<template id="pending-student-', $html);
        $this->assertStringContainsString('margin-bottom:7px;overflow:hidden', $html);
    }

    public function test_each_claim_section_paginates_ten_student_groups(): void
    {
        $admin = $this->createSuperAdmin();
        $item = $this->item();

        for ($i = 1; $i <= 12; $i++) {
            $student = $this->createStudent(null, [
                'first_name' => 'Group' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);
            $this->claim($student, $item, ['status' => 'Pending']);
            $this->claim($student, $item, ['status' => 'Approved', 'approved_by' => $admin->id]);
            $this->claim($student, $item, ['status' => 'Rejected']);
            $this->claim($student, $item, [
                'status' => 'Approved',
                'is_archived' => true,
                'released_at' => now(),
            ]);
        }

        $pending = $this->actingAs($admin)->get(route('admin.claims.pending.filter'));
        $pending->assertOk()->assertSee('of 12 students');
        $this->assertSame(10, substr_count((string) $pending->getContent(), 'class="approved-student-card"'));

        $approved = $this->actingAs($admin)->get(route('admin.claims.approved.filter'));
        $approved->assertOk()->assertSee('of 12 students');
        $this->assertSame(10, substr_count((string) $approved->getContent(), 'class="approved-student-card"'));

        $history = $this->actingAs($admin)->get(route('admin.claims.history.filter'));
        $history->assertOk()->assertSee('of 12 students');
        $this->assertSame(10, substr_count((string) $history->getContent(), 'class="approved-student-card"'));

        $page = $this->actingAs($admin)->get(route('admin.claims.index'));
        $page->assertOk();
        $html = (string) $page->getContent();
        $this->assertStringContainsString('archived_page=2', $html);
        $this->assertStringContainsString('pending_page=2', $html);
        $this->assertStringContainsString('approved_page=2', $html);
        $this->assertStringContainsString('history_page=2', $html);
    }

    public function test_rejected_claims_remain_visible_in_history_popup_data(): void
    {
        $admin = $this->createSuperAdmin();
        $item = $this->item(['item_name' => 'Rejected Marker']);
        $student = $this->createStudent(null, ['first_name' => 'History', 'last_name' => 'Student']);
        $this->claim($student, $item, ['status' => 'Rejected', 'remarks' => 'Rejected for test']);

        $response = $this->actingAs($admin)->get(route('admin.claims.history.filter', [
            'history_search' => 'Rejected Marker',
        ]));

        $response->assertOk()->assertSee('Rejected Marker')->assertSee('status-badge rejected');
    }

    public function test_filter_changes_reset_the_affected_section_page(): void
    {
        $admin = $this->createSuperAdmin();
        $item = $this->item();
        $student = $this->createStudent(null, ['first_name' => 'Reset', 'last_name' => 'Target']);
        $this->claim($student, $item);

        $response = $this->actingAs($admin)->get(route('admin.claims.pending.filter', [
            'pending_student_search' => 'Reset',
            'pending_page' => 5,
        ]));

        $response->assertOk()->assertSee('of 1 students');
        $source = (string) file_get_contents(resource_path('views/pages/claims.blade.php'));
        $this->assertStringContainsString("params.set('pending_page', '1')", $source);
        $this->assertStringContainsString("params.set('history_page', '1')", $source);
        $this->assertStringContainsString("params.set('approved_page', '1')", $source);
    }
}
