<?php

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
            'email_verification_code' => $emailChanged ? null : $user->email_verification_code,
            'email_verification_expires_at' => $emailChanged ? null : $user->email_verification_expires_at,
        ]);

        $description = $emailChanged
            ? 'Updated their account name and email address.'
            : 'Updated their account name.';

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Updated Profile',
            'description' => $description,
            'module' => 'Account Settings',
        ]);

        $message = $emailChanged
            ? 'Email address updated. Please verify your new email address.'
            : 'Profile updated successfully.';

        return redirect()->route('settings.edit')->with('success', $message);
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
