<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Mail\SendOtpMail;
use App\Models\User;
use App\Models\Teacher;
use App\Models\AdminActivity;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $field => $login,
            'password' => $request->password,
        ];

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('verification.notice', ['email' => $user->email])
                    ->with('info', 'Please verify your email address before logging in.');
            }

            $request->session()->regenerate();

            AdminActivity::create([
                'user_id' => Auth::id(),
                'action' => 'Login',
                'description' => 'User logged into the system.',
                'module' => 'Auth',
            ]);

            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'position' => 'nullable|string|max:100',
        ]);

        $role = 'teacher';

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'position' => $validated['position'],
            'status' => 'active',
            'email_verified_at' => null,
            'email_verification_code' => Hash::make($otp),
            'email_verification_expires_at' => now()->addMinutes(10),
        ]);

        Teacher::create([
            'admin_id' => 'ADM' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'position' => $validated['position'],
            'status' => 'active',
        ]);

        try {
            Mail::to($user->email)->send(new SendOtpMail($otp, $user->name));
            \Log::info('OTP email sent successfully to: ' . $user->email);
        } catch (\Exception $e) {
            \Log::error('OTP email failed during registration: ' . $e->getMessage());
            $user->delete();
            return redirect()->route('register')
                ->withInput()
                ->withErrors(['email' => 'Unable to send verification email. Please check the mail server settings or try again later.']);
        }

        return redirect()->route('verification.notice', ['email' => $user->email])
            ->with('success', 'Account created successfully. Please check your email for the verification code.');
    }

    public function showVerify(Request $request)
    {
        $email = $request->query('email');

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasVerifiedEmail()) {
                return redirect()->route('admin.dashboard');
            }
            $email = $email ?: $user->email;
        }

        if (!$email) {
            return redirect()->route('login');
        }

        return view('auth.verify-email', compact('email'));
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user->hasVerifiedEmail()) {
            return Auth::check()
                ? redirect()->route('admin.dashboard')->with('success', 'Email already verified.')
                : redirect()->route('login')->with('success', 'Email already verified. You may now log in.');
        }

        if (!$user->email_verification_code || !Hash::check($validated['otp'], $user->email_verification_code)) {
            return back()->withErrors([
                'otp' => 'The verification code you entered is incorrect.',
            ])->withInput();
        }

        if (!$user->email_verification_expires_at || now()->gt($user->email_verification_expires_at)) {
            return back()->withErrors([
                'otp' => 'The verification code has expired. Please request a new one.',
            ]);
        }

        $user->markEmailAsVerified();

        if (Auth::check()) {
            return redirect()->route('admin.dashboard')->with('success', 'Email verified successfully.');
        }

        return redirect()->route('login')->with('success', 'Email verified successfully. You may now log in.');
    }

    public function resendOtp(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);
            $user = User::where('email', $validated['email'])->first();
        }

        if ($user->hasVerifiedEmail()) {
            return Auth::check()
                ? redirect()->route('settings.edit')->with('success', 'Email already verified.')
                : redirect()->route('login')->with('success', 'Email already verified. You may now log in.');
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'email_verification_code' => Hash::make($otp),
            'email_verification_expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($user->email)->send(new SendOtpMail($otp, $user->name));
            \Log::info('OTP email sent successfully to: ' . $user->email);
        } catch (\Exception $e) {
            \Log::error('OTP email failed on resend: ' . $e->getMessage());
            $user->update([
                'email_verification_code' => null,
                'email_verification_expires_at' => null,
            ]);
            return back()->withErrors([
                'email' => 'Unable to send verification email. Please check the mail server settings or try again later.',
            ]);
        }

        if (Auth::check()) {
            return redirect()->route('verification.notice', ['email' => $user->email])
                ->with('success', 'A verification code has been sent to your email.');
        }

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    public function showForgotPassword()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword($token)
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password changed successfully. You may now log in.')
            : back()->withErrors(['email' => [__($status)]]);
    }

    public function logout(Request $request)
    {
        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Logout',
            'description' => 'User logged out of the system.',
            'module' => 'Auth',
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}
