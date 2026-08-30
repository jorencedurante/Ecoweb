<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\SendOtpMail;

class SetupController extends Controller
{
    public function show()
    {
        if (User::where('role', 'super_admin')->exists()) {
            return redirect()->route('login')
                ->with('error', 'System setup is already complete.');
        }

        return view('setup.create-super-admin');
    }

    public function store(Request $request)
    {
        if (User::where('role', 'super_admin')->exists()) {
            return redirect()->route('login')
                ->with('error', 'System setup is already complete.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'super_admin',
            'status' => 'active',
            'email_verified_at' => null,
            'email_verification_code' => Hash::make($otp),
            'email_verification_expires_at' => now()->addMinutes(10),
        ]);

        Auth::login($user);

        try {
            Mail::to($user->email)->send(new SendOtpMail($otp, $user->name));
            Log::info('Setup Super Admin OTP sent to: ' . $user->email);
        } catch (\Exception $e) {
            Log::error('Setup Super Admin OTP failed: ' . $e->getMessage());
        }

        return redirect()->route('verification.notice', ['email' => $user->email])
            ->with('success', 'First Super Admin account created. Please verify your email using the code sent to your email.');
    }
}
