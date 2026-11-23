<?php

namespace Tests\Feature;

use App\Models\ClaimItem;
use App\Models\StudentClaim;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase5UIFixesTest extends TestCase
{
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

    public function test_archived_account_actions_stay_on_one_line(): void
    {
        $source = (string) file_get_contents(resource_path('views/pages/teachers.blade.php'));

        $this->assertStringContainsString(
            'flex-direction:row;align-items:center;justify-content:center;gap:10px;flex-wrap:nowrap',
            $source
        );
        $this->assertStringContainsString('width:132px;min-width:132px', $source);
        $this->assertStringContainsString('action-btn-restore unarchive', $source);
        $this->assertStringContainsString('action-btn-delete delete-archived', $source);

        $response = $this->actingAs($this->createSuperAdmin())->get(route('admin.teachers'));
        $response->assertOk()->assertSee('Archived Accounts');
    }

    public function test_create_account_422_errors_are_shown_beside_the_failed_field(): void
    {
        $source = (string) file_get_contents(resource_path('views/pages/teachers.blade.php'));

        // Inline error slots for every server-validated field.
        foreach (['ctNameError', 'ctUsernameError', 'ctEmailError', 'ctPositionError', 'ctRoleError',
            'ctTeacherIdError', 'ctAccessLevelError', 'ctCustomPermissionsError'] as $errorId) {
            $this->assertStringContainsString('id="' . $errorId . '"', $source);
        }
        $this->assertStringContainsString('applyServerErrors(data.errors)', $source);
        $this->assertStringContainsString('if (createTeacherSubmitting) return;', $source);
        $this->assertStringContainsString("key.split('.')[0]", $source); // custom_permissions.0

        $admin = $this->createSuperAdmin();

        // Valid data is accepted - validation was not weakened.
        $this->actingAs($admin)->postJson(route('admin.teachers.create-account'), [
            'name' => 'UI Fix Teacher', 'username' => 'ui.fix.teacher',
            'email' => 'ui-fix-teacher@example.com', 'position' => 'Teacher', 'role' => 'teacher',
        ])->assertOk()->assertJsonPath('success', true);

        // Invalid data still returns 422 with a field-keyed error body for the UI to render.
        $this->actingAs($admin)->postJson(route('admin.teachers.create-account'), [
            'name' => 'No Position', 'username' => 'ui.fix.nopos',
            'email' => 'ui-fix-nopos@example.com', 'role' => 'teacher',
        ])->assertStatus(422)->assertJsonPath('errors.position.0', 'The position field is required.');

        $this->actingAs($admin)->postJson(route('admin.teachers.create-account'), [
            'name' => 'Duplicate', 'username' => 'ui.fix.duplicate',
            'email' => $admin->email, 'position' => 'Teacher', 'role' => 'teacher',
        ])->assertStatus(422)->assertJsonPath('errors.email.0', 'The email has already been taken.');

        $this->assertDatabaseMissing('users', ['email' => 'ui-fix-nopos@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'ui.fix.duplicate']);

        // Role restrictions remain intact.
        $this->actingAs($this->createAdmin())->postJson(route('admin.teachers.create-account'), [
            'name' => 'Escalation', 'username' => 'ui.fix.escalation',
            'email' => 'ui-fix-escalation@example.com', 'position' => 'Principal', 'role' => 'super_admin',
        ])->assertForbidden();
    }

    public function test_settings_card_is_narrower_and_centered(): void
    {
        $source = (string) file_get_contents(resource_path('views/pages/settings.blade.php'));

        $this->assertStringContainsString('.settings-section .card{max-width:800px;margin-left:auto;margin-right:auto;}', $source);
        $this->assertStringContainsString('.settings-section .card-body{max-width:600px;margin-left:auto;margin-right:auto;}', $source);
        $this->assertStringContainsString('@media (max-width:820px){.settings-section .card{max-width:100%;}}', $source);
        $this->assertStringContainsString('max-width:600px;margin:0 auto', $source);

        $principal = $this->createSuperAdmin();
        $this->actingAs($principal)->get(route('admin.system-settings'))->assertOk()->assertSee('admin_name');

        $this->actingAs($principal)->post(route('admin.settings.general'), [
            'admin_name' => 'Compact Card Admin',
            'school_organization' => 'Compact Card School',
            'address' => 'Compact Card Address',
        ])->assertRedirect();

        $this->assertDatabaseHas('system_settings', ['admin_name' => 'Compact Card Admin']);
    }

    public function test_claim_tables_and_rows_are_compact(): void
    {
        $files = [
            resource_path('views/pages/claims.blade.php'),
            resource_path('views/partials/pending-claims-table.blade.php'),
            resource_path('views/partials/approved-claims-table.blade.php'),
            resource_path('views/partials/claim-history-table.blade.php'),
        ];

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            $this->assertStringContainsString('padding:10px 16px;cursor:pointer;flex-wrap:wrap;gap:6px', $source, $file);
            $this->assertStringContainsString('margin-bottom:7px;overflow:hidden', $source, $file);
            $this->assertStringContainsString('padding:8px 10px', $source, $file);
            $this->assertStringContainsString('font-size:12px;text-transform:uppercase', $source, $file);
        }

        $claimsSource = (string) file_get_contents($files[0]);
        $this->assertStringContainsString('#claim-items-section table th { padding: 9px 12px; font-size: 12px; }', $claimsSource);
        $this->assertStringContainsString('.data-card-header { padding: 14px 20px; }', $claimsSource);

        $admin = $this->createSuperAdmin();
        $item = ClaimItem::create([
            'item_name' => 'Compact Item', 'description' => 'Compact', 'points_required' => 10,
            'quantity' => 5, 'status' => 'Available',
        ]);
        $student = $this->createStudent(null, ['first_name' => 'Compact', 'last_name' => 'Student']);
        $this->claim($student, $item);

        $this->actingAs($admin)->get(route('admin.claims.index'))
            ->assertOk()->assertSee('Claim Items')->assertSee('Compact Student');
    }

    public function test_release_confirmations_use_custom_modal_and_bulk_logic_is_unchanged(): void
    {
        $approvedSource = (string) file_get_contents(resource_path('views/partials/approved-claims-table.blade.php'));
        $claimsSource = (string) file_get_contents(resource_path('views/pages/claims.blade.php'));

        // No browser confirm() left on the release actions.
        $this->assertStringNotContainsString('confirm(', $approvedSource);
        $this->assertStringContainsString("'Mark Item as Released?'", $approvedSource);
        $this->assertStringContainsString("'Mark All Items as Released?'", $approvedSource);
        $this->assertStringContainsString('This action will record the item as released and move it to the released item requests history.', $approvedSource);
        $this->assertStringContainsString('mark all eligible approved item requests for this student as released?', $approvedSource);

        // The custom dialog sits above the details popup and handles both release types.
        $this->assertStringContainsString('#claimDetailsModal { z-index: 3000; }', $claimsSource);
        $this->assertStringContainsString('#claimConfirmModal { z-index: 4000; }', $claimsSource);
        $this->assertStringContainsString("type === 'release_all'", $claimsSource);
        $this->assertStringContainsString("actionBtn.textContent = 'Confirm Release'", $claimsSource);
        $this->assertStringContainsString("actionBtn.textContent = 'Confirm All Releases'", $claimsSource);
        $this->assertStringContainsString('claim-confirm-icon', $claimsSource);
        $this->assertStringContainsString('id="claimConfirmCancel"', $claimsSource);

        // Functional check: release only archives eligible approved claims.
        $admin = $this->createSuperAdmin();
        $item = ClaimItem::create([
            'item_name' => 'Release Item', 'description' => 'Release', 'points_required' => 10,
            'quantity' => 10, 'status' => 'Available',
        ]);
        $student = $this->createStudent(null, ['first_name' => 'Release', 'last_name' => 'Student']);

        $approved = $this->claim($student, $item, ['status' => 'Approved', 'approved_by' => $admin->id]);
        $pending = $this->claim($student, $item, ['status' => 'Pending']);
        $released = $this->claim($student, $item, [
            'status' => 'Approved', 'approved_by' => $admin->id,
            'is_archived' => true, 'released_at' => now()->subDay(), 'released_by' => $admin->id,
        ]);
        $originalReleaseDate = $released->released_at;

        $this->actingAs($admin)->patch(route('admin.claims.archiveAllByStudent', $student->id))
            ->assertRedirect();

        $this->assertDatabaseHas('student_claims', [
            'id' => $approved->id, 'is_archived' => true, 'status' => 'Approved',
            'released_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('student_claims', [
            'id' => $pending->id, 'is_archived' => false, 'released_by' => null,
        ]);
        $this->assertDatabaseHas('student_claims', [
            'id' => $released->id, 'released_at' => $originalReleaseDate,
        ]);

        // Single release records one activity entry and does not touch points.
        $pointsBefore = $approved->points_after;
        $this->actingAs($admin)->patch(route('admin.claims.archive', $approved->id))->assertRedirect();
        $this->assertSame(1, DB::table('admin_activities')
            ->where('module', 'Claims')
            ->where('description', 'like', '%Release Item%')
            ->where('action', 'Archived Item Request')
            ->count());
        $this->assertSame($pointsBefore, $approved->fresh()->points_after);
        $this->assertSame('Approved', $approved->fresh()->status);
    }
}
