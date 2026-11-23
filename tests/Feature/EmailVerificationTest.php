<?php

namespace Tests\Feature;

use App\Mail\SendOtpMail;
use App\Mail\TeacherSetupInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_public_registration_is_blocked_and_cannot_create_accounts(): void
    {
        $this->get(route('register'))->assertNotFound();

        $response = $this->post(route('register.submit'), [
            'name' => 'Blocked Registration',
            'username' => 'blockedreg',
            'email' => 'blocked-registration@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'position' => 'Teacher',
        ]);
        $response->assertNotFound();

        $this->assertNull(User::where('email', 'blocked-registration@example.com')->first());
        Mail::assertNothingSent();
    }

    public function test_only_admin_can_create_teacher_accounts_and_send_invitations(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson(route('admin.teachers.create-account'), [
            'name' => 'Invited Teacher',
            'username' => 'invited.teacher',
            'email' => 'invited-teacher@example.com',
            'position' => 'Teacher',
        ]);
        $response->assertOk()->assertJsonPath('success', true)->assertJsonPath('invitation_sent', true);

        $invited = User::where('email', 'invited-teacher@example.com')->firstOrFail();
        $this->assertSame('teacher', $invited->role);
        $this->assertSame('pending_setup', $invited->status);
        $this->assertNotNull($invited->email_verification_code);
        Mail::assertSent(TeacherSetupInvitation::class, function ($mail) use ($invited) {
            return $mail->hasTo($invited->email) && str_contains($mail->setupUrl, 'teacher-setup/' . $invited->id);
        });

        $teacher = $this->createTeacher();
        $this->actingAs($teacher)->postJson(route('admin.teachers.create-account'), [
            'name' => 'Denied Teacher',
            'username' => 'denied.teacher',
            'email' => 'denied-teacher@example.com',
            'position' => 'Teacher',
        ])->assertForbidden();

        $this->assertNull(User::where('email', 'denied-teacher@example.com')->first());
    }

    public function test_wrong_otp_is_rejected(): void
    {
        $user = $this->createTeacher([
            'email_verified_at' => null,
            'email_verification_code' => Hash::make('123456'),
            'email_verification_expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->post(route('verification.verify'), [
            'email' => $user->email,
            'otp' => '999999',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertNull($user->refresh()->email_verified_at);
    }

    public function test_correct_otp_verifies_the_email(): void
    {
        $user = $this->createTeacher([
            'email_verified_at' => null,
            'email_verification_code' => Hash::make('123456'),
            'email_verification_expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->post(route('verification.verify'), [
            'email' => $user->email,
            'otp' => '123456',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = $this->createTeacher([
            'email_verified_at' => null,
            'email_verification_code' => Hash::make('123456'),
            'email_verification_expires_at' => now()->subMinutes(30),
        ]);

        $response = $this->post(route('verification.verify'), [
            'email' => $user->email,
            'otp' => '123456',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertNull($user->refresh()->email_verified_at);
    }

    public function test_resend_otp_sends_a_new_code(): void
    {
        $user = $this->createTeacher([
            'email_verified_at' => null,
            'email_verification_code' => null,
        ]);

        $response = $this->post(route('verification.resend'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('success');
        Mail::assertSent(SendOtpMail::class);

        $this->assertNotNull($user->refresh()->email_verification_code);
    }
}
