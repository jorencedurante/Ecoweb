<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdminActivity;
use App\Mail\SendOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['admin', 'teacher', 'super_admin'])
            ->where('status', '!=', 'archived');

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

    public function fetch(Request $request)
    {
        $query = User::whereIn('role', ['admin', 'teacher', 'super_admin'])
            ->where('status', '!=', 'archived');

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

        $accounts = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'accounts' => $accounts->items(),
            'current_page' => $accounts->currentPage(),
            'last_page' => $accounts->lastPage(),
            'per_page' => $accounts->perPage(),
            'total' => $accounts->total(),
            'from' => $accounts->firstItem(),
            'to' => $accounts->lastItem(),
            'next_page_url' => $accounts->nextPageUrl(),
            'prev_page_url' => $accounts->previousPageUrl(),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can create accounts.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,teacher,super_admin',
            'position' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,restricted,archived',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['email_verified_at'] = null;

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $validated['email_verification_code'] = Hash::make($otp);
        $validated['email_verification_expires_at'] = now()->addMinutes(10);

        $user = User::create($validated);

        try {
            Mail::to($user->email)->send(new SendOtpMail($otp, $user->name));
            Log::info('OTP email sent successfully to: ' . $user->email);
        } catch (\Exception $e) {
            Log::error('OTP email failed on account creation: ' . $e->getMessage());
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

        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can edit or change user roles.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => 'nullable|required_with:password|string',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,teacher,super_admin',
            'position' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,restricted,archived',
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

        if ($user->isSuperAdmin() && $validated['role'] !== 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();

            if ($superAdminCount <= 1) {
                return back()
                    ->withErrors(['role' => 'You cannot remove the last Super Admin account.'])
                    ->withInput();
            }
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

    public function restrict(Request $request, $id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['error' => 'Only Super Admin can restrict accounts.'], 403);
        }

        if ((int) $id === (int) auth()->id()) {
            return response()->json(['error' => 'You cannot restrict your own account.'], 422);
        }

        $user = User::findOrFail($id);

        if ($user->status === 'restricted') {
            return response()->json(['error' => 'Account is already restricted.'], 422);
        }

        if ($user->status === 'archived') {
            return response()->json(['error' => 'Cannot restrict an archived account. Reactivate it first.'], 422);
        }

        $user->forceFill(['status' => 'restricted', 'remember_token' => null])->save();

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Restricted Account',
            'description' => "Restricted {$user->role} account: {$user->name}.",
            'module' => 'Accounts',
        ]);

        return response()->json([
            'success' => ucfirst($user->role) . ' account restricted successfully!',
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }

    public function unrestrict(Request $request, $id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['error' => 'Only Super Admin can unrestrict accounts.'], 403);
        }

        if ((int) $id === (int) auth()->id()) {
            return response()->json(['error' => 'You cannot unrestrict your own account.'], 422);
        }

        $user = User::findOrFail($id);

        if ($user->status !== 'restricted') {
            return response()->json(['error' => 'Only restricted accounts can be unrestricted.'], 422);
        }

        $user->forceFill(['status' => 'active'])->save();

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Unrestricted Account',
            'description' => "Unrestricted {$user->role} account: {$user->name}.",
            'module' => 'Accounts',
        ]);

        return response()->json([
            'success' => ucfirst($user->role) . ' account unrestricted successfully!',
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }

    public function archive(Request $request, $id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['error' => 'Only Super Admin can archive accounts.'], 403);
        }

        if ((int) $id === (int) auth()->id()) {
            return response()->json(['error' => 'You cannot archive your own account.'], 422);
        }

        $user = User::findOrFail($id);

        if ($user->status === 'archived') {
            return response()->json(['error' => 'Account is already archived.'], 422);
        }

        $user->forceFill(['status' => 'archived', 'remember_token' => null])->save();

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Archived Account',
            'description' => "Archived {$user->role} account: {$user->name}.",
            'module' => 'Accounts',
        ]);

        return response()->json([
            'success' => ucfirst($user->role) . ' account archived successfully!',
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }

    public function reactivate(Request $request, $id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['error' => 'Only Super Admin can reactivate accounts.'], 403);
        }

        if ((int) $id === (int) auth()->id()) {
            return response()->json(['error' => 'You cannot reactivate your own account.'], 422);
        }

        $user = User::findOrFail($id);

        if ($user->status === 'active') {
            return response()->json(['error' => 'Account is already active.'], 422);
        }

        if (!in_array($user->status, ['archived', 'restricted'])) {
            return response()->json(['error' => 'Only archived or restricted accounts can be reactivated.'], 422);
        }

        $user->forceFill(['status' => 'active'])->save();

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Reactivated Account',
            'description' => "Reactivated {$user->role} account: {$user->name}.",
            'module' => 'Accounts',
        ]);

        return response()->json([
            'success' => ucfirst($user->role) . ' account reactivated successfully!',
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }

    public function archived(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['error' => 'Only Super Admin can view archived accounts.'], 403);
        }

        $query = User::where('status', 'archived')->whereIn('role', ['admin', 'teacher', 'super_admin']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'accounts' => $accounts,
        ]);
    }
}
