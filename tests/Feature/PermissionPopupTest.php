<?php

namespace Tests\Feature;

use App\Exceptions\StudentPermissionDeniedException;
use App\Models\User;
use Tests\TestCase;

class PermissionPopupTest extends TestCase
{
    private const ADD_MESSAGE = "You don't have permission to add students. Please contact your Teacher or Principal to request access.";

    private function subTeacher(User $teacher, string $level = 'view_only', array $custom = []): User
    {
        $sub = $this->createAccount(['role' => 'sub_teacher']);
        $sub->forceFill([
            'assigned_teacher_id' => $teacher->id,
            'sub_teacher_access_level' => $level,
            'sub_teacher_permissions' => $custom,
        ])->save();

        return $sub;
    }

    private function payload(string $lrn): array
    {
        return [
            'lrn' => $lrn,
            'first_name' => 'Permission',
            'last_name' => 'Test',
            'grade_level' => 'Grade 1',
            'gender' => 'Female',
            'school_year' => '2026-2027',
        ];
    }

    public function test_teacher_can_add_students_and_sees_allowed_button(): void
    {
        $teacher = $this->createTeacher();

        $this->actingAs($teacher)->get(route('teacher.students'))
            ->assertOk()
            ->assertSee('data-permission-action="add" data-permission-allowed="true"', false);

        $this->actingAs($teacher)->post(route('teacher.students.store'), $this->payload('108663060501'))
            ->assertRedirect(route('teacher.students'));

        $this->assertDatabaseHas('students', ['lrn' => '108663060501']);
    }

    public function test_view_only_sub_teacher_can_view_students_but_add_button_shows_popup(): void
    {
        $teacher = $this->createTeacher();
        $sub = $this->subTeacher($teacher, 'view_only');

        $response = $this->actingAs($sub)->get(route('teacher.students'))->assertOk();

        // View permission is separate: the page itself still loads.
        $response->assertSee('Permission Required')
            ->assertSee('have permission to add students. Please contact your Teacher or Principal')
            ->assertSee('OK, Got It')
            ->assertSee('data-permission-action="add" data-permission-allowed="false"', false)
            ->assertSee('M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01', false);
    }

    public function test_sub_teacher_with_add_permission_can_add_students(): void
    {
        $teacher = $this->createTeacher();
        $sub = $this->subTeacher($teacher, 'collaborator');

        $this->actingAs($sub)->get(route('teacher.students'))
            ->assertOk()
            ->assertSee('data-permission-action="add" data-permission-allowed="true"', false);

        $this->actingAs($sub)->post(route('teacher.students.store'), $this->payload('108663060502'))
            ->assertRedirect(route('teacher.students'));

        $this->assertDatabaseHas('students', ['lrn' => '108663060502']);
    }

    public function test_direct_denied_submission_redirects_with_flash_and_creates_nothing(): void
    {
        $teacher = $this->createTeacher();
        $sub = $this->subTeacher($teacher);

        $response = $this->actingAs($sub)->post(route('teacher.students.store'), $this->payload('108663060503'));

        $response->assertRedirect()
            ->assertSessionHas('permission_denied.action', 'add')
            ->assertSessionHas('permission_denied.permission_denied', true)
            ->assertSessionHasInput('first_name', 'Permission');

        $this->assertDatabaseMissing('students', ['lrn' => '108663060503']);
    }

    public function test_ajax_denial_returns_403_json(): void
    {
        $teacher = $this->createTeacher();
        $sub = $this->subTeacher($teacher);

        $this->actingAs($sub)->postJson(route('teacher.students.store'), $this->payload('108663060504'))
            ->assertForbidden()
            ->assertJsonPath('permission_denied', true)
            ->assertJsonPath('action', 'add')
            ->assertJsonPath('error', self::ADD_MESSAGE);

        $this->assertDatabaseMissing('students', ['lrn' => '108663060504']);
    }

    public function test_flash_permission_message_is_available_to_the_popup(): void
    {
        $teacher = $this->createTeacher();

        $response = $this->actingAs($teacher)
            ->withSession(['permission_denied' => [
                'permission_denied' => true,
                'action' => 'edit',
                'message' => "You don't have permission to edit students. Please contact your Teacher or Principal to request access.",
            ]])
            ->get(route('teacher.students'));

        $response->assertOk()->assertSee('window.ecPermissionDenied =', false)
            ->assertSee('have permission to edit students. Please contact your Teacher or Principal', false);
    }

    public function test_all_modal_opening_paths_are_permission_guarded(): void
    {
        $appJs = (string) file_get_contents(public_path('js/app.js'));
        $studentsView = (string) file_get_contents(resource_path('views/pages/students.blade.php'));
        $partial = (string) file_get_contents(resource_path('views/partials/permission-denied-modal.blade.php'));

        $this->assertStringContainsString('window.EcoPermission.guard', $appJs);
        $this->assertStringContainsString('if (!window.EcoPermission.guard(btn)) return;', $appJs);
        // Delegated table handler (re-rendered rows) and the direct edit handler.
        $this->assertStringContainsString('if (!window.EcoPermission.guard(modalBtn)) return;', $studentsView);
        $this->assertStringContainsString('if (!window.EcoPermission.guard(btn)) return;', $studentsView);
        // The guard runs before any modal state is changed.
        $this->assertLessThan(
            strpos($studentsView, 'modal.classList.add(\'show\')'),
            strpos($studentsView, 'window.EcoPermission.guard(modalBtn)')
        );
        $this->assertLessThan(
            strpos($studentsView, 'populateEditStudentForm(btn)'),
            strpos($studentsView, 'window.EcoPermission.guard(btn)')
        );

        $this->assertStringContainsString('OK, Got It', $partial);
        $this->assertStringContainsString('data-modal-close', $partial);
    }

    public function test_action_specific_messages(): void
    {
        $this->assertSame(self::ADD_MESSAGE, (new StudentPermissionDeniedException('add'))->getMessage());
        $this->assertSame(
            "You don't have permission to edit students. Please contact your Teacher or Principal to request access.",
            (new StudentPermissionDeniedException('edit'))->getMessage()
        );
        $this->assertSame(
            "You don't have permission to delete students. Please contact your Teacher or Principal to request access.",
            (new StudentPermissionDeniedException('delete'))->getMessage()
        );
    }
}
