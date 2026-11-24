<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PasswordToggleUiTest extends TestCase
{
    private function assertStandardToggle($response, string $target, string $label = 'Show password'): void
    {
        $response->assertOk()
            ->assertSee('class="password-toggle" data-target="' . $target . '"', false)
            ->assertSee('aria-label="' . $label . '"', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertDontSee('👁');
    }

    public function test_login_uses_the_standard_eye_icon_and_toggle(): void
    {
        $response = $this->get(route('login'));
        $this->assertStandardToggle($response, 'password-login');
        $response->assertSee('M2.062 12.348a1 1 0 0 1 0-.696', false);
    }

    public function test_reset_password_has_independent_standard_toggles(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'test-token']));

        $this->assertStandardToggle($response, 'reset-password');
        $this->assertStandardToggle($response, 'reset-password-confirmation');
    }

    public function test_teacher_setup_has_independent_standard_toggles(): void
    {
        $token = 'plain-setup-token';
        $teacher = $this->createTeacher([
            'status' => 'pending_setup',
            'email_verification_code' => Hash::make($token),
            'email_verification_expires_at' => now()->addHour(),
        ]);

        $url = URL::temporarySignedRoute('teacher.setup.show', now()->addHour(), [
            'user' => $teacher->id,
            'token' => $token,
        ]);

        $response = $this->get($url);
        $this->assertStandardToggle($response, 'password');
        $this->assertStandardToggle($response, 'password_confirmation');
    }

    public function test_account_settings_password_fields_use_standard_toggles(): void
    {
        $response = $this->actingAs($this->createTeacher())->get(route('teacher.settings.edit'));

        $this->assertStandardToggle($response, 'cpCurrentPassword');
        $this->assertStandardToggle($response, 'cpPassword');
        $this->assertStandardToggle($response, 'cpPasswordConfirmation');
    }

    public function test_admin_account_password_fields_use_standard_toggles(): void
    {
        $response = $this->actingAs($this->createSuperAdmin())->get(route('admin.teachers'));

        $this->assertStandardToggle($response, 'teacherCurrentPasswordInput');
        $this->assertStandardToggle($response, 'teacherNewPasswordInput');
        $this->assertStandardToggle($response, 'teacherConfirmPasswordInput');
    }

    public function test_shared_helper_is_the_only_toggle_behavior(): void
    {
        $js = (string) file_get_contents(public_path('js/app.js'));

        $this->assertStringContainsString('window.EcoPasswordToggle', $js);
        $this->assertStringContainsString('M17.94 11.34a10 10 0 0 1-4.11 0', $js);
        $this->assertStringNotContainsString('👁', $js);

        foreach (['auth/login.blade.php', 'auth/reset-password.blade.php',
            'auth/teacher-setup.blade.php', 'setup/create-super-admin.blade.php',
            'pages/account-settings.blade.php', 'pages/teachers.blade.php'] as $view) {
            $source = (string) file_get_contents(resource_path('views/' . $view));
            $this->assertStringNotContainsString('Toggle password visibility', $source, $view);
            $this->assertStringNotContainsString('👁', $source, $view);
        }
    }
}
