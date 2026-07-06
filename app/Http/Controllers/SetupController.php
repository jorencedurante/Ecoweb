<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SetupController extends Controller
{
    public function show()
    {
        if (User::where('role', 'Super Admin')->exists()) {
            return redirect()->route('login')
                ->with('error', 'System setup is already complete.');
        }

        return view('setup.create-super-admin');
    }

    public function store(Request $request)
    {
        if (User::where('role', 'Super Admin')->exists()) {
            return redirect()->route('login')
                ->with('error', 'System setup is already complete.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'Super Admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        return redirect()->route('login')
            ->with('success', 'First Super Admin account created successfully. You can now login.');
    }
}
