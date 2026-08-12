<?php

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use App\Models\User;
use App\Mail\EmailChangeOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AccountSettingsController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('pages.account-settings', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $emailChanged = $validated['email'] !== $user->email;

        if ($emailChanged) {
            $oldEmail = $user->email;
            $newEmail = $validated['email'];

            if ($user->pending_email === $newEmail) {
                return redirect()->route('account.email-change.verify')
                    ->with('info', 'A verification code was already sent to your current email.');
            }

            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $user->update([
                'name' => $validated['name'],
                'pending_email' => $newEmail,
                'email_change_otp' => Hash::make($otp),
                'email_change_otp_expires_at' => now()->addMinutes(10),
            ]);

            try {
                Mail::to($oldEmail)->send(new EmailChangeOtpMail($otp, $user->name));
                \Log::info('Email change OTP sent to old email: ' . $oldEmail);
            } catch (\Exception $e) {
                \Log::error('Email change OTP failed: ' . $e->getMessage());
                $user->update([
                    'email_change_otp' => null,
                    'email_change_otp_expires_at' => null,
                ]);
                return back()->withErrors([
                    'email' => 'Unable to send verification email. Please try again later.',
                ])->withInput();
            }

            AdminActivity::create([
                'user_id' => Auth::id(),
                'action' => 'Requested Email Change',
                'description' => "Requested to change email to {$newEmail}.",
                'module' => 'Account Settings',
            ]);

            return redirect()->route('account.email-change.verify')
                ->with('success', 'A verification code was sent to your current email address.');
        }

        $user->update([
            'name' => $validated['name'],
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Updated Profile',
            'description' => 'Updated their account name.',
            'module' => 'Account Settings',
        ]);

        return redirect()->route('settings.edit')
            ->with('success', 'Profile updated successfully.');
    }

    public function showEmailChangeVerify()
    {
        $user = Auth::user();

        if (!$user->pending_email) {
            return redirect()->route('settings.edit')
                ->with('error', 'No pending email change request found.');
        }

        return view('pages.email-change-verify', compact('user'));
    }

    public function confirmEmailChange(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user = Auth::user();

        if (!$user->pending_email) {
            return redirect()->route('settings.edit')
                ->with('error', 'No pending email change request found.');
        }

        if (!$user->email_change_otp_expires_at || now()->greaterThan($user->email_change_otp_expires_at)) {
            return back()->withErrors([
                'otp' => 'The verification code has expired. Please request a new code.',
            ]);
        }

        if (!Hash::check((string) $request->otp, $user->email_change_otp)) {
            return back()->withErrors([
                'otp' => 'The verification code is incorrect. Please try again.',
            ]);
        }

        $newEmail = $user->pending_email;

        $user->update([
            'email' => $newEmail,
            'pending_email' => null,
            'email_change_otp' => null,
            'email_change_otp_expires_at' => null,
            'email_verified_at' => now(),
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Changed Email',
            'description' => "Changed email address to {$newEmail}.",
            'module' => 'Account Settings',
        ]);

        return redirect()->route('settings.edit')
            ->with('success', 'Your email address has been changed successfully.');
    }

    public function resendEmailChangeOtp()
    {
        $user = Auth::user();

        if (!$user->pending_email) {
            return redirect()->route('settings.edit')
                ->with('error', 'No pending email change request found.');
        }

        $oldEmail = $user->email;

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'email_change_otp' => Hash::make($otp),
            'email_change_otp_expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($oldEmail)->send(new EmailChangeOtpMail($otp, $user->name));
            \Log::info('Email change OTP resent to old email: ' . $oldEmail);
        } catch (\Exception $e) {
            \Log::error('Email change OTP resend failed: ' . $e->getMessage());
            $user->update([
                'email_change_otp' => null,
                'email_change_otp_expires_at' => null,
            ]);
            return back()->withErrors([
                'otp' => 'Unable to send verification email. Please try again later.',
            ]);
        }

        return back()->with('success', 'A new verification code was sent to your current email address.');
    }

    public function cancelEmailChange()
    {
        $user = Auth::user();

        $user->update([
            'pending_email' => null,
            'email_change_otp' => null,
            'email_change_otp_expires_at' => null,
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Cancelled Email Change',
            'description' => 'Cancelled an email change request.',
            'module' => 'Account Settings',
        ]);

        return redirect()->route('settings.edit')
            ->with('success', 'Email change request cancelled.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ])->withInput();
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Updated Password',
            'description' => 'Changed their account password.',
            'module' => 'Account Settings',
        ]);

        return redirect()->route('settings.edit')
            ->with('success', 'Password updated successfully.');
    }
}
