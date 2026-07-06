<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use App\Models\AdminActivity;
use App\Mail\SendOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['admin', 'teacher', 'super_admin']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $accounts = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('pages.teachers', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,teacher,super_admin',
            'position' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,archived',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['email_verified_at'] = null;

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $validated['email_verification_code'] = Hash::make($otp);
        $validated['email_verification_expires_at'] = now()->addMinutes(10);

        $user = User::create($validated);

        if ($user->role === 'teacher') {
            Teacher::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'admin_id' => 'ADM' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                    'name' => $user->name,
                    'email' => $user->email,
                    'position' => $validated['position'] ?? 'Teacher',
                    'status' => $validated['status'] ?? 'active',
                ]
            );
        }

        try {
            Mail::to($user->email)->send(new SendOtpMail($otp, $user->name));
            \Log::info('OTP email sent successfully to: ' . $user->email);
        } catch (\Exception $e) {
            \Log::error('OTP email failed on account creation: ' . $e->getMessage());
            $user->delete();
            return redirect()->back()->withInput()->withErrors(['email' => 'Unable to send verification email. Please check the mail server settings or try again later.']);
        }

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Added Account',
            'description' => "Added {$user->role} account: {$user->name} ({$user->username}).",
            'module' => 'Accounts',
        ]);

        return redirect()->route('admin.teachers')->with('success', ucfirst($user->role) . ' account added successfully! A verification code has been sent to their email.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => 'nullable|required_with:password|string',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,teacher,super_admin',
            'position' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,archived',
        ]);

        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()
                    ->withErrors(['current_password' => 'The current password is incorrect.'])
                    ->withInput();
            }
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Edited Account',
            'description' => "Edited {$user->role} account: {$user->name}.",
            'module' => 'Accounts',
        ]);

        return redirect()->route('admin.teachers')->with('success', ucfirst($user->role) . ' account updated successfully!');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'archived']);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Archived Account',
            'description' => "Archived {$user->role} account: {$user->name}.",
            'module' => 'Accounts',
        ]);

        return redirect()->route('admin.teachers')->with('success', ucfirst($user->role) . ' account archived successfully!');
    }
}
