<?php

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use App\Models\User;
use App\Mail\EmailChangeOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AccountSettingsController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('pages.account-settings', compact('user'));
    }

    /**
     * Helper: return JSON for AJAX, redirect otherwise.
     */
    private function respond($success, $message, $redirect = null, $status = 200, $errors = null)
    {
        if ($request = request()) {
            if ($request->expectsJson() || $request->ajax()) {
                $payload = ['success' => $success, 'message' => $message];
                if ($errors) {
                    $payload['errors'] = $errors;
                }
                return response()->json($payload, $status);
            }
        }
        if ($success) {
            return redirect($redirect)->with('success', $message);
        }
        return back()->withErrors($errors ? $errors : ['general' => $message])->withInput();
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user->update([
            'name' => $validated['name'],
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Updated Profile',
            'description' => 'Updated their account name.',
            'module' => 'Account Settings',
        ]);

        return $this->respond(true, 'Profile updated successfully.', route('settings.edit'));
    }

    public function initiateEmailChange(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'new_email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $newEmail = $validated['new_email'];

        if ($user->pending_email === $newEmail) {
            return $this->respond(false, 'A verification code was already sent for this email address.', null, 422);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'pending_email' => $newEmail,
            'email_change_otp' => Hash::make($otp),
            'email_change_otp_expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($user->email)->send(new EmailChangeOtpMail($otp, $user->name));
            Log::info('Email change OTP sent to current email: ' . $user->email);
        } catch (\Throwable $e) {
            Log::error('Email change OTP sending failed', [
                'user_id' => auth()->id(),
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            $user->update([
                'pending_email' => null,
                'email_change_otp' => null,
                'email_change_otp_expires_at' => null,
            ]);
            return $this->respond(false, 'Unable to send verification email. Please try again later.', null, 500);
        }

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Requested Email Change',
            'description' => "Requested to change email to {$newEmail}.",
            'module' => 'Account Settings',
        ]);

        return $this->respond(true, 'A verification code was sent to your current email address.');
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

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'email_change_otp' => Hash::make($otp),
            'email_change_otp_expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($user->email)->send(new EmailChangeOtpMail($otp, $user->name));
            Log::info('Email change OTP resent to current email: ' . $user->email);
        } catch (\Throwable $e) {
            Log::error('Email change OTP sending failed', [
                'user_id' => auth()->id(),
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            $user->update([
                'pending_email' => null,
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
            return $this->respond(false, 'The current password is incorrect.', null, 422, ['current_password' => 'The current password is incorrect.']);
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

        return $this->respond(true, 'Password updated successfully.', route('settings.edit'));
    }
}
