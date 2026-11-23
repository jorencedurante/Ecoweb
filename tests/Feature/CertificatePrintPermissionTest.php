<?php

namespace Tests\Feature;

use App\Models\CertificateAward;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificatePrintPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function awardFor($student, $teacher): CertificateAward
    {
        $path = 'certificate-awards/print-permission-' . $student->id . '.jpg';
        Storage::disk('local')->put($path, 'certificate-image-content');

        return CertificateAward::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'award_title' => 'Print Permission Certificate',
            'awarded_date' => now()->toDateString(),
            'generated_file_path' => $path,
            'status' => 'Active',
            'issued_by' => $teacher->id,
        ]);
    }

    public function test_teacher_without_grant_cannot_print_and_sees_clear_notice(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);
        $award = $this->awardFor($student, $teacher);

        $page = $this->actingAs($teacher)->get(route('teacher.certificate'));
        $page->assertOk()->assertSee('Certificate printing access has not been granted. Please contact your administrator.', false);
        $this->assertStringContainsString('id="previewPrintBtn" class="btn btn-sm" style="background:#6B7280;color:#fff;padding:10px 24px;" disabled', (string) $page->getContent());

        $this->actingAs($teacher)->get(route('teacher.certificate.print', $award))->assertForbidden();
        $this->actingAs($teacher)->get(route('teacher.certificate.file', $award))->assertForbidden();
        $this->actingAs($teacher)->get(route('teacher.certificate.download', $award))->assertForbidden();

        $overview = $this->actingAs($teacher)->getJson(route('teacher.students.overview', $student));
        $overview->assertOk()
            ->assertJsonPath('certificates.0.print_url', null)
            ->assertJsonPath('certificates.0.view_url', null);
    }

    public function test_admin_grants_and_revokes_certificate_printing_access(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);
        $award = $this->awardFor($student, $teacher);

        // No automatic grant for existing/new teachers.
        $this->assertFalse($teacher->fresh()->canPrintCertificates());

        $this->actingAs($admin)->patch(route('admin.teachers.certificate-permissions', $teacher), [
            'can_print_certificates' => true,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('can_print_certificates', true);

        $this->assertTrue($teacher->fresh()->canPrintCertificates());
        $this->actingAs($teacher->fresh())->get(route('teacher.certificate.print', $award))->assertOk();
        $this->actingAs($teacher->fresh())->get(route('teacher.certificate.file', $award))->assertOk();
        $this->actingAs($teacher->fresh())->get(route('teacher.certificate.download', $award))->assertOk();

        $this->actingAs($admin)->patch(route('admin.teachers.certificate-permissions', $teacher), [
            'can_print_certificates' => false,
        ])->assertOk()->assertJsonPath('can_print_certificates', false);

        $this->assertFalse($teacher->fresh()->canPrintCertificates());
        $this->actingAs($teacher->fresh())->get(route('teacher.certificate.print', $award))->assertForbidden();
        $this->assertSame(2, \Illuminate\Support\Facades\DB::table('admin_activities')
            ->where('module', 'Accounts')->where('user_id', $admin->id)
            ->whereIn('action', ['Granted Certificate Printing Access', 'Revoked Certificate Printing Access'])
            ->count());
    }

    public function test_teacher_and_sub_teacher_cannot_grant_permissions_to_themselves(): void
    {
        $teacher = $this->createTeacher();
        $subTeacher = $this->createAccount([
            'name' => 'Self Grant Sub', 'role' => 'sub_teacher', 'status' => 'active',
        ]);

        foreach ([$teacher, $subTeacher] as $actor) {
            $this->actingAs($actor)->patch(route('admin.teachers.certificate-permissions', $actor), [
                'can_print_certificates' => true,
            ])->assertForbidden();
        }

        $this->assertFalse($teacher->fresh()->canPrintCertificates());
        $this->assertFalse($subTeacher->fresh()->canPrintCertificates());
    }

    public function test_sub_teacher_does_not_inherit_printing_permission(): void
    {
        $teacher = $this->createTeacher();
        $teacher->forceFill(['can_print_certificates' => true])->save();
        $student = $this->createStudent($teacher);
        $award = $this->awardFor($student, $teacher);

        $subTeacher = $this->createAccount([
            'name' => 'Printing Sub Teacher', 'role' => 'sub_teacher', 'status' => 'active',
            'assigned_teacher_id' => $teacher->id,
            'sub_teacher_access_level' => 'collaborator',
        ]);

        // Assigned to a granted Teacher, but no own grant: still blocked.
        $this->assertFalse($subTeacher->fresh()->canPrintCertificates());
        $this->actingAs($subTeacher)->get(route('teacher.certificate.print', $award))->assertForbidden();

        // Explicit grant from Admin is required.
        $this->actingAs($this->createAdmin())->patch(
            route('admin.teachers.certificate-permissions', $subTeacher),
            ['can_print_certificates' => true]
        )->assertOk();

        $this->assertTrue($subTeacher->fresh()->canPrintCertificates());
        $this->actingAs($subTeacher->fresh())->get(route('teacher.certificate.print', $award))->assertOk();

        // Assignment is still required: an unassigned granted Sub Teacher is blocked.
        $unassigned = $this->createAccount([
            'name' => 'Unassigned Printing Sub', 'role' => 'sub_teacher', 'status' => 'active',
            'can_print_certificates' => true,
        ]);
        $this->assertFalse($unassigned->fresh()->canPrintCertificates());
    }

    public function test_signatory_dropdowns_show_name_and_position_only(): void
    {
        $admin = $this->createAdmin(['name' => 'Karen Ballesteros', 'position' => 'Administrator']);
        $principal = $this->createSuperAdmin(['name' => 'Lenny Gloton Caoayan', 'position' => 'Principal']);
        $teacher = $this->createTeacher(['name' => 'Joshua Garcia', 'position' => 'Teacher 4']);

        $html = (string) $this->actingAs($admin)->get(route('admin.certificate'))->getContent();

        $this->assertStringContainsString('Select Adviser', $html);
        $this->assertStringContainsString('Select the adviser whose name will appear on the certificate.', $html);
        $this->assertStringContainsString('Joshua Garcia — Teacher 4', $html);
        $this->assertStringContainsString('Karen Ballesteros — Administrator', $html);
        $this->assertStringContainsString('Lenny Gloton Caoayan — Principal', $html);

        foreach ([' · Teacher', ' · Admin', ' · Super admin', ' · Sub teacher'] as $roleLabel) {
            $this->assertStringNotContainsString($roleLabel, $html);
        }

        // Eligibility is unchanged: Sub Teachers do not appear in either dropdown.
        $subTeacher = $this->createAccount(['name' => 'Hidden Sub', 'role' => 'sub_teacher', 'status' => 'active']);
        $htmlWithSub = (string) $this->actingAs($admin)->get(route('admin.certificate'))->getContent();
        $this->assertStringNotContainsString('Hidden Sub', $htmlWithSub);
        $this->assertTrue($teacher->isTeacher() && $principal->isSuperAdmin());
    }

    public function test_admin_account_management_has_permission_control_and_custom_confirmation(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher(['name' => 'Permission Target', 'position' => 'Teacher 4']);
        $teacher->forceFill(['can_print_certificates' => true])->save();

        $html = (string) $this->actingAs($superAdmin)->get(route('admin.teachers'))->getContent();

        foreach ([
            'Certificate Permissions',
            'Certificate Printing Access',
            'Grant Certificate Printing Access?',
            'Revoke Certificate Printing Access?',
            'Allow this teacher to print certificates in EcoCollect?',
            'This teacher will no longer be authorized to print certificates.',
            'Grant Access',
            'Revoke Access',
            'confirmCertificatePrintPermission',
            'data-can-print-certificates="1"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringNotContainsString('window.confirm', $html);
    }

    public function test_admin_and_principal_keep_existing_printing_ability(): void
    {
        $admin = $this->createAdmin();
        $principal = $this->createSuperAdmin();
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);
        $award = $this->awardFor($student, $teacher);

        $this->assertTrue($admin->canPrintCertificates());
        $this->assertTrue($principal->canPrintCertificates());

        foreach ([$admin, $principal] as $actor) {
            $this->actingAs($actor)->get(route('admin.certificate.print', $award))->assertOk();
            $this->actingAs($actor)->get(route('admin.certificate.file', $award))->assertOk();
            $this->actingAs($actor)->get(route('admin.certificate.download', $award))->assertOk();
        }
    }
}
