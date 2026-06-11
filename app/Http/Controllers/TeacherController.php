<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $user = User::create($validated);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Added Account',
            'description' => "Added {$user->role} account: {$user->name} ({$user->username}).",
            'module' => 'Accounts',
        ]);

        return redirect()->route('admin.teachers')->with('success', ucfirst($user->role) . ' account added successfully!');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,teacher,super_admin',
            'position' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,archived',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
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
