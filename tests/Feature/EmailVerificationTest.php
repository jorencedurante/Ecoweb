<?php

namespace Tests\Feature;

use App\Mail\SendOtpMail;
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

    public function test_registration_creates_an_unverified_teacher_account_only_in_users(): void
    {
        $response = $this->post(route('register.submit'), [
            'name' => 'Registered Teacher',
            'username' => 'regteacher1',
            'email' => 'regteacher1@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'position' => 'Teacher 1',
        ]);

        $response->assertRedirect(route('verification.notice', ['email' => 'regteacher1@example.com']));

        $user = User::where('email', 'regteacher1@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('teacher', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertNull($user->email_verified_at);

        $this->assertDatabaseMissing('teachers', ['email' => 'regteacher1@example.com']);
        Mail::assertSent(SendOtpMail::class);
    }

    public function test_registration_cannot_create_a_super_admin_account(): void
    {
        $this->post(route('register.submit'), [
            'name' => 'Sneaky User',
            'username' => 'sneaky1',
            'email' => 'sneaky1@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'super_admin',
        ]);

        $user = User::where('email', 'sneaky1@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('teacher', $user->role);
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
