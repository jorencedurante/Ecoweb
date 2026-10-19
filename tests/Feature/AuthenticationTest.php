<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_super_admin_can_login_with_username(): void
    {
        $user = $this->createSuperAdmin(['username' => 'chiefauth', 'email' => 'chiefauth@example.com']);

        $response = $this->post(route('login.submit'), [
            'login' => 'chiefauth',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_login_with_email(): void
    {
        $user = $this->createAdmin(['email' => 'adminauth@example.com']);

        $response = $this->post(route('login.submit'), [
            'login' => 'adminauth@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_teacher_can_login_with_username(): void
    {
        $user = $this->createTeacher(['username' => 'teacherauth', 'email' => 'teacherauth@example.com']);

        $response = $this->post(route('login.submit'), [
            'login' => 'teacherauth',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->createTeacher(['username' => 'wrongpw', 'email' => 'wrongpw@example.com']);

        $response = $this->post(route('login.submit'), [
            'login' => 'wrongpw',
            'password' => 'not-the-password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_unverified_user_is_redirected_to_verification(): void
    {
        $user = $this->createTeacher([
            'username' => 'unverified1',
            'email' => 'unverified1@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->post(route('login.submit'), [
            'login' => 'unverified1',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('verification.notice', ['email' => $user->email]));
        $this->assertGuest();
    }

    public function test_logout_destroys_the_session(): void
    {
        $user = $this->createTeacher();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
