<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdminActivity;
use App\Services\CollaborationService;
use App\Mail\SendOtpMail;
use App\Mail\EmailChangeOtpMail;
use App\Mail\TeacherSetupInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['admin', 'teacher', 'sub_teacher', 'super_admin'])
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

    /**
     * Active Teacher accounts eligible for Sub Teacher assignment.
     * Loaded on demand so account lists never leak unrelated names into HTML.
     */
    public function eligibleForAssignment()
    {
        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['teachers' => $teachers]);
    }

    public function fetch(Request $request)
    {
        $query = User::whereIn('role', ['admin', 'teacher', 'sub_teacher', 'super_admin'])
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
            'role' => 'required|in:admin,teacher,sub_teacher,super_admin',
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

        return redirect()->route(portal_prefix() . '.teachers')->with('success', User::roleLabel($user->role) . ' account added successfully! A verification code has been sent to their email.');
    }

    /**
     * Create a teacher in Pending Setup and email a secure, single-use setup link.
     */
    public function createTeacherAccount(Request $request)
    {
        $actor = auth()->user();
        if (!$actor->isAdminLevel()) {
            abort(403, 'Only Admin and Principal can create staff accounts.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'position' => 'required|string|max:100',
            // Optional for backward compatibility with existing invitation callers;
            // the Create Account form always sends an explicit role.
            'role' => 'sometimes|in:teacher,sub_teacher,admin,super_admin',
            // Delegated access is only meaningful for Sub Teachers.
            'teacher_id' => ['nullable', 'integer', Rule::exists('users', 'id')
                ->where('role', 'teacher')->where('status', 'active')],
            'access_level' => 'nullable|in:collaborator,view_only,custom',
            'custom_permissions' => 'nullable|array',
            'custom_permissions.*' => 'string|in:' . implode(',', array_keys(CollaborationService::configurablePermissions())),
        ]);

        $role = $validated['role'] ?? 'teacher';
        // Admin may onboard teachers and sub teachers; only the existing
        // Super Admin (displayed as Principal) may create higher-privilege roles.
        if (in_array($role, ['admin', 'super_admin'], true) && !$actor->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only Principal can create Admin or Principal accounts.',
            ], 403);
        }

        $token = Str::random(64);
        $expiresAt = now()->addHours(72);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'position' => $validated['position'],
            'role' => $role,
            'status' => 'pending_setup',
            'password' => Hash::make(Str::random(48)),
            'email_verified_at' => null,
            'email_verification_code' => Hash::make($token),
            'email_verification_expires_at' => $expiresAt,
        ]);

        // A Sub Teacher may be assigned at creation time. The assignment is
        // stored on this user's own row; student ownership is unchanged.
        if ($role === 'sub_teacher' && !empty($validated['teacher_id'])) {
            $accessLevel = $validated['access_level'] ?? CollaborationService::ACCESS_COLLABORATOR;

            $user->forceFill([
                'assigned_teacher_id' => $validated['teacher_id'],
                'sub_teacher_access_level' => $accessLevel,
                'sub_teacher_permissions' => $accessLevel === CollaborationService::ACCESS_CUSTOM
                    ? ($validated['custom_permissions'] ?? [])
                    : null,
            ])->save();
        }

        $setupUrl = URL::temporarySignedRoute('teacher.setup.show', $expiresAt, [
            'user' => $user->id,
            'token' => $token,
        ]);

        $invitationSent = true;
        try {
            Mail::to($user->email)->send(new TeacherSetupInvitation($user->name, $setupUrl, 72));
        } catch (\Throwable $e) {
            Log::error('Teacher setup invitation failed: ' . $e->getMessage());
            $invitationSent = false;
        }

        $roleLabel = User::roleLabel($role);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Created ' . $roleLabel . ' Account',
            'description' => "Created {$roleLabel} account: {$user->name} ({$user->username}).",
            'module' => 'Accounts',
        ]);

        return response()->json([
            'success' => true,
            'invitation_sent' => $invitationSent,
            'message' => $invitationSent
                ? "{$roleLabel} account created successfully. An account setup invitation has been sent to the new account's email."
                : "{$roleLabel} account created, but the invitation could not be sent. Please resend it from the accounts table.",
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'status' => $user->status,
            ],
        ]);
    }

    private function setupInvitationError(Request $request, User $user): ?string
    {
        $token = (string) $request->query('token', '');

        if (!in_array($user->role, ['teacher', 'sub_teacher', 'admin', 'super_admin'], true)
            || $user->status !== 'pending_setup') {
            return 'This account setup invitation is no longer valid.';
        }
        if ($token === '' || !$user->email_verification_code || !Hash::check($token, $user->email_verification_code)) {
            return 'This account setup link is invalid or has already been used.';
        }
        if (!$user->email_verification_expires_at || now()->gt($user->email_verification_expires_at)) {
            return 'This account setup link has expired. Please ask an administrator to resend the invitation.';
        }

        return null;
    }

    public function showTeacherSetupForm(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $error = $this->setupInvitationError($request, $user);

        if ($error) {
            $status = str_contains(strtolower($error), 'expired') ? 410 : 404;
            return response()->view('auth.teacher-setup-invalid', ['error' => $error], $status);
        }

        return view('auth.teacher-setup', [
            'user' => $user,
            'token' => (string) $request->query('token', ''),
        ]);
    }

    public function completeTeacherSetup(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $error = $this->setupInvitationError($request, $user);

        if ($error) {
            return back()->withErrors(['setup' => $error]);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'status' => 'active',
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
            'remember_token' => null,
        ])->save();

        $roleLabel = User::roleLabel($user->role);

        AdminActivity::create([
            'user_id' => $user->id,
            'action' => 'Activated ' . $roleLabel . ' Account',
            'description' => "{$roleLabel} account activated: {$user->name} ({$user->username}).",
            'module' => 'Accounts',
        ]);

        return redirect()->route('login')->with('success', 'Your account has been activated. Please log in to continue.');
    }

    public function resendInvitation($id)
    {
        if (!auth()->user()->isAdminLevel()) {
            abort(403, 'Only Admin and Super Admin can resend invitations.');
        }

        $user = User::findOrFail($id);
        if (!in_array($user->role, ['teacher', 'sub_teacher', 'admin', 'super_admin'], true)
            || $user->status !== 'pending_setup') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending staff accounts can receive an invitation.',
            ], 422);
        }

        $token = Str::random(64);
        $expiresAt = now()->addHours(72);
        $user->forceFill([
            'email_verification_code' => Hash::make($token),
            'email_verification_expires_at' => $expiresAt,
        ])->save();

        $setupUrl = URL::temporarySignedRoute('teacher.setup.show', $expiresAt, [
            'user' => $user->id,
            'token' => $token,
        ]);

        try {
            Mail::to($user->email)->send(new TeacherSetupInvitation($user->name, $setupUrl, 72));
        } catch (\Throwable $e) {
            Log::error('Teacher setup invitation resend failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to resend the invitation. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'A new account setup invitation has been sent.',
        ]);
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
            'role' => 'required|in:admin,teacher,sub_teacher,super_admin',
            'position' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,restricted,archived',
        ]);

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

        return redirect()->route(portal_prefix() . '.teachers')->with('success', User::roleLabel($user->role) . ' account updated successfully!');
    }

    /**
     * Grant or revoke the explicit certificate printing permission.
     * Admin and Principal (Super Admin) are the only authorized managers.
     * Sub Teachers never inherit this permission from their Teacher.
     */
    public function updateCertificatePermissions(Request $request, $id)
    {
        if (!auth()->user()->isAdminLevel()) {
            return response()->json(['error' => 'Only Admin and Principal can manage certificate printing access.'], 403);
        }

        $user = User::findOrFail($id);

        if (!$user->isTeacher() && !$user->isSubTeacher()) {
            return response()->json([
                'error' => 'Certificate printing access can only be managed for Teacher and Sub Teacher accounts.',
            ], 422);
        }

        $validated = $request->validate([
            'can_print_certificates' => 'required|boolean',
        ]);

        $granted = (bool) $validated['can_print_certificates'];
        $user->forceFill(['can_print_certificates' => $granted])->save();

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => $granted ? 'Granted Certificate Printing Access' : 'Revoked Certificate Printing Access',
            'description' => ($granted ? 'Granted' : 'Revoked') . " certificate printing access for {$user->name} ({$user->username}).",
            'module' => 'Accounts',
        ]);

        return response()->json([
            'success' => true,
            'can_print_certificates' => $granted,
            'message' => $granted
                ? 'Certificate printing access granted successfully.'
                : 'Certificate printing access revoked successfully.',
        ]);
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
            'success' => User::roleLabel($user->role) . ' account restricted successfully!',
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
            'success' => User::roleLabel($user->role) . ' account unrestricted successfully!',
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
            'success' => User::roleLabel($user->role) . ' account archived successfully!',
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
            'success' => User::roleLabel($user->role) . ' account reactivated successfully!',
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

        $query = User::where('status', 'archived')->whereIn('role', ['admin', 'teacher', 'sub_teacher', 'super_admin']);

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

    /**
     * Permanently delete an archived account, but only when no related
     * records would be destroyed or orphaned by the deletion.
     */
    public function deleteArchived(Request $request, $id)
    {
        $actor = auth()->user();
        if (!$actor || !$actor->isSuperAdmin()) {
            return response()->json(['error' => 'Only Principal can permanently delete accounts.'], 403);
        }

        if ((int) $id === (int) $actor->id) {
            return response()->json(['error' => 'You cannot delete your own account.'], 422);
        }

        $user = User::findOrFail($id);

        if ($user->status !== 'archived') {
            return response()->json(['error' => 'Only archived accounts can be permanently deleted.'], 422);
        }

        // Protect the last remaining Principal (super_admin) account.
        if ($user->isSuperAdmin()) {
            $remaining = User::where('role', 'super_admin')
                ->where('id', '!=', $user->id)
                ->count();

            if ($remaining === 0) {
                return response()->json(['error' => 'The last Principal account cannot be deleted.'], 422);
            }
        }

        // Block destructive or orphaning deletes before touching the record.
        $blockers = [];

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'teacher_id')) {
            $count = DB::table('students')->where('teacher_id', $user->id)->count();
            if ($count > 0) {
                $blockers[] = "{$count} student record(s) are still assigned to this account";
            }
        }

        if (Schema::hasTable('student_enrollments') && Schema::hasColumn('student_enrollments', 'teacher_id')) {
            $count = DB::table('student_enrollments')->where('teacher_id', $user->id)->count();
            if ($count > 0) {
                $blockers[] = "{$count} enrollment record(s) reference this account";
            }
        }

        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'user_id')) {
            $count = DB::table('teachers')->where('user_id', $user->id)->count();
            if ($count > 0) {
                $blockers[] = 'a linked legacy teacher record exists for this account';
            }
        }

        if (!empty($blockers)) {
            return response()->json([
                'error' => 'This account cannot be deleted safely: ' . implode('; ', $blockers) . '. Reassign or remove those records first.',
                'blockers' => $blockers,
            ], 422);
        }

        $name = $user->name;
        $username = $user->username;
        $roleLabel = User::roleLabel($user->role);
        $actorId = $actor->id;

        // admin_activities.user_id uses nullOnDelete, so the history rows
        // survive with their description intact after this delete.
        $user->delete();

        AdminActivity::create([
            'user_id' => $actorId,
            'action' => 'Deleted Archived Account',
            'description' => "Permanently deleted archived {$roleLabel} account: {$name} ({$username}).",
            'module' => 'Accounts',
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$roleLabel} account permanently deleted.",
        ]);
    }

    public function initiateEmailChange(Request $request, $id)
    {
        $acting = auth()->user();
        if (!$acting || !$acting->isSuperAdmin()) {
            return $this->teacherRespond(false, 'Only Super Admin can change account email.', 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'new_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $newEmail = strtolower(trim((string) $validated['new_email']));
        $currentEmail = (string) ($user->email ?? '');

        if ($currentEmail === '' || is_null($user->email_verified_at)) {
            return $this->teacherRespond(false, 'The current account email is unavailable or unverified. Use the secure account-recovery process before changing this email.', 422, ['new_email' => 'Current account email must be verified before it can be changed.']);
        }

        if (strcasecmp($currentEmail, $newEmail) === 0) {
            return $this->teacherRespond(false, 'The new email must be different from the current email.', 422, ['new_email' => 'The new email must be different from the current email.']);
        }

        $state = $this->getTeacherEmailChangeState($user);
        if ($state && isset($state['last_sent_at'])) {
            $elapsed = now()->diffInSeconds(\Carbon\Carbon::parse($state['last_sent_at']));
            if ($elapsed < 60) {
                $wait = 60 - $elapsed;
                return $this->teacherRespond(false, "Please wait {$wait} seconds before requesting another verification code.", 429);
            }
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $state = [
            'purpose' => 'teacher_email_change_current_otp',
            'target_id' => (int) $user->id,
            'acting_id' => (int) $acting->id,
            'current_email' => $currentEmail,
            'proposed_email' => $newEmail,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'attempts' => 0,
            'last_sent_at' => now()->toIso8601String(),
        ];

        $this->putTeacherEmailChangeState($user, $state);
        $this->clearTeacherLegacyEmailChangeColumns($user);

        try {
            Mail::to($currentEmail)->send(new EmailChangeOtpMail($otp, $user->name, 'authorize_current_email'));
        } catch (\Throwable $e) {
            Log::error('Teacher email change OTP send failed', [
                'target_user_id' => $user->id,
                'acting_user_id' => $acting->id,
            ]);
            $this->forgetTeacherEmailChangeState($user);
            return $this->teacherRespond(false, 'Unable to send the verification email. Please try again later.', 500);
        }

        AdminActivity::create([
            'user_id' => $acting->id,
            'action' => 'Requested Account Email Change Verification',
            'description' => "Requested current-email verification for account ID {$user->id} email change.",
            'module' => 'Accounts',
        ]);

        return $this->teacherRespond(true, 'Verification code sent to the current registered email.', 200);
    }

    public function confirmEmailChange(Request $request, $id)
    {
        $acting = auth()->user();
        if (!$acting || !$acting->isSuperAdmin()) {
            return $this->teacherRespond(false, 'Only Super Admin can change account email.', 403);
        }

        $validated = $request->validate([
            'new_email' => ['required', 'email', 'max:255'],
            'otp' => 'required|digits:6',
        ]);

        $user = User::findOrFail($id);
        $newEmail = strtolower(trim((string) $validated['new_email']));
        $state = $this->getTeacherEmailChangeState($user);

        if (!$state) {
            return $this->teacherRespond(false, 'No pending email change request found for this account.', 422);
        }

        $bindingError = $this->validateTeacherEmailChangeState($state, $user, $acting, $newEmail);
        if ($bindingError) {
            $this->forgetTeacherEmailChangeState($user);
            return $this->teacherRespond(false, $bindingError, 422);
        }

        $expiresAt = isset($state['expires_at']) ? \Carbon\Carbon::parse($state['expires_at']) : null;
        if (!$expiresAt || now()->greaterThan($expiresAt)) {
            $this->forgetTeacherEmailChangeState($user);
            return $this->teacherRespond(false, 'The verification code has expired. Please request a new code.', 422, ['otp' => 'The verification code has expired. Please request a new code.']);
        }

        if (!Hash::check((string) $validated['otp'], (string) ($state['otp_hash'] ?? ''))) {
            $state['attempts'] = (int) ($state['attempts'] ?? 0) + 1;
            if ($state['attempts'] >= 5) {
                $this->forgetTeacherEmailChangeState($user);
                return $this->teacherRespond(false, 'Too many incorrect verification attempts. Please request a new verification code.', 422, ['otp' => 'Too many incorrect attempts. Please request a new code.']);
            }
            $this->putTeacherEmailChangeState($user, $state);
            return $this->teacherRespond(false, 'The verification code is incorrect. Please try again.', 422, ['otp' => 'The verification code is incorrect. Please try again.']);
        }

        if (User::whereRaw('LOWER(email) = ?', [$newEmail])->where('id', '!=', $user->id)->exists()) {
            $this->forgetTeacherEmailChangeState($user);
            return $this->teacherRespond(false, 'This email address is already in use.', 422, ['new_email' => 'This email address is already in use.']);
        }

        $user->forceFill([
            'email' => $newEmail,
            'pending_email' => null,
            'email_change_otp' => null,
            'email_change_otp_expires_at' => null,
            'email_verified_at' => null,
        ])->save();

        $this->forgetTeacherEmailChangeState($user);

        AdminActivity::create([
            'user_id' => $acting->id,
            'action' => 'Changed Account Email',
            'description' => "Changed email for account ID {$user->id} after current-email verification.",
            'module' => 'Accounts',
        ]);

        return $this->teacherRespond(true, 'Email updated successfully.', 200, null, ['email' => $newEmail]);
    }

    private function teacherEmailChangeCacheKey(User $user): string
    {
        return 'teacher_email_change:' . $user->id;
    }

    private function getTeacherEmailChangeState(User $user): ?array
    {
        $state = Cache::get($this->teacherEmailChangeCacheKey($user));
        return is_array($state) ? $state : null;
    }

    private function putTeacherEmailChangeState(User $user, array $state): void
    {
        Cache::put($this->teacherEmailChangeCacheKey($user), $state, now()->addMinutes(10));
    }

    private function forgetTeacherEmailChangeState(User $user): void
    {
        Cache::forget($this->teacherEmailChangeCacheKey($user));
        $this->clearTeacherLegacyEmailChangeColumns($user);
    }

    private function clearTeacherLegacyEmailChangeColumns(User $user): void
    {
        if ($user->pending_email || $user->email_change_otp || $user->email_change_otp_expires_at) {
            $user->forceFill([
                'pending_email' => null,
                'email_change_otp' => null,
                'email_change_otp_expires_at' => null,
            ])->save();
        }
    }

    private function validateTeacherEmailChangeState(array $state, User $user, User $acting, string $newEmail): ?string
    {
        if (($state['purpose'] ?? null) !== 'teacher_email_change_current_otp') {
            return 'The email change request is invalid. Please start again.';
        }
        if ((int) ($state['target_id'] ?? 0) !== (int) $user->id) {
            return 'The email change request does not match this account. Please start again.';
        }
        if ((int) ($state['acting_id'] ?? 0) !== (int) $acting->id) {
            return 'This email change request must be completed by the administrator who started it.';
        }
        $currentEmail = (string) ($user->email ?? '');
        if ($currentEmail === '' || is_null($user->email_verified_at)) {
            return 'The current account email is unavailable or unverified. Use the secure account-recovery process before changing this email.';
        }
        if (strcasecmp((string) ($state['current_email'] ?? ''), $currentEmail) !== 0) {
            return 'The current email address has changed. Please start the process again.';
        }
        if (strcasecmp((string) ($state['proposed_email'] ?? ''), $newEmail) !== 0) {
            return 'The new email address changed. Please start the verification process again.';
        }
        return null;
    }

    public function changePassword(Request $request, $id)
    {
        $actingUser = auth()->user();
        if (!$actingUser->isSuperAdmin()) {
            return $this->teacherRespond(false, 'Only Super Admin can change account passwords.', 403);
        }

        $user = User::findOrFail($id);
        $isSelf = (int) $actingUser->id === (int) $user->id;

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $actingUser->password)) {
            return $this->teacherRespond(false, 'The current password is incorrect.', 422, ['current_password' => 'The current password is incorrect.']);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Changed Account Password',
            'description' => "Changed password for {$user->name}.",
            'module' => 'Accounts',
        ]);

        return $this->teacherRespond(true, 'Password updated successfully.');
    }

    private function teacherRespond(bool $success, string $message, int $status = 200, ?array $errors = null, array $extra = []): \Illuminate\Http\JsonResponse
    {
        $payload = array_merge([
            'success' => $success,
            'message' => $message,
        ], $extra);
        if ($errors) {
            $payload['errors'] = $errors;
        }
        return response()->json($payload, $status);
    }
}
