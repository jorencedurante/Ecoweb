<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    public function test_forgot_password_flow_resets_the_password(): void
    {
        Notification::fake();

        $teacher = $this->createTeacher(['email' => 'recover1@example.com']);

        $this->post(route('password.email'), ['email' => 'recover1@example.com']);

        Notification::assertSentTo($teacher, ResetPassword::class);

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $teacher->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $reset = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'recover1@example.com',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $reset->assertRedirect(route('login'));
        $reset->assertSessionHas('success');

        $login = $this->post(route('login.submit'), [
            'login' => 'recover1@example.com',
            'password' => 'brand-new-password',
        ]);

        $login->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($teacher->fresh());
    }

    public function test_reset_with_invalid_token_fails(): void
    {
        $this->createTeacher(['email' => 'recover2@example.com']);

        $response = $this->post(route('password.update'), [
            'token' => 'totally-invalid-token',
            'email' => 'recover2@example.com',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_teacher_login_still_works_after_password_reset_only(): void
    {
        $teacher = $this->createTeacher(['email' => 'recover3@example.com', 'password' => 'original-password']);

        $login = $this->post(route('login.submit'), [
            'login' => 'recover3@example.com',
            'password' => 'original-password',
        ]);

        $login->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($teacher);
    }
}
