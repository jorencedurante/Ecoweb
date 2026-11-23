<?php

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use App\Models\User;
use App\Services\CollaborationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;

class SubTeacherAssignmentController extends Controller
{
    /**
     * Shared query builder for the management page and its AJAX endpoint.
     */
    private function buildQuery(Request $request)
    {
        $query = User::where('role', 'sub_teacher')->with('assignedTeacher');

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhereHas('assignedTeacher', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%")
                         ->orWhere('username', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->get('status')) {
            if ($status === 'unassigned') {
                $query->whereNull('assigned_teacher_id');
            } elseif ($status === 'assigned') {
                $query->whereNotNull('assigned_teacher_id');
            } elseif ($status === 'inactive') {
                $query->whereNotNull('assigned_teacher_id')->where('status', '!=', 'active');
            }
        }

        return $query->orderBy('name');
    }

    public function index(Request $request)
    {
        $subTeachers = $this->buildQuery($request)->paginate(10)->withQueryString();

        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.sub-teacher-assignments', compact('subTeachers', 'teachers'));
    }

    /**
     * AJAX endpoint: returns only the results body and pagination markup.
     */
    public function data(Request $request)
    {
        $subTeachers = $this->buildQuery($request)->paginate(10);

        $html = View::make('partials.sub-teacher-assignment-results', compact('subTeachers'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'pagination' => [
                'current_page' => $subTeachers->currentPage(),
                'last_page' => $subTeachers->lastPage(),
                'total' => $subTeachers->total(),
                'from' => $subTeachers->firstItem(),
                'to' => $subTeachers->lastItem(),
            ],
        ]);
    }

    public function show(User $subTeacher)
    {
        $this->ensureSubTeacher($subTeacher);

        return response()->json([
            'assignment' => [
                'id' => $subTeacher->id,
                'sub_teacher_id' => $subTeacher->id,
                'sub_teacher_name' => $subTeacher->name,
                'teacher_id' => $subTeacher->assigned_teacher_id,
                'teacher_name' => $subTeacher->assignedTeacher->name ?? '—',
                'access_level' => $subTeacher->sub_teacher_access_level ?? '—',
                'custom_permissions' => $subTeacher->sub_teacher_permissions ?? [],
                'status' => $subTeacher->assignmentStatus(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAssignment($request);
        $subTeacher = User::findOrFail($validated['sub_teacher_id']);
        $this->ensureSubTeacher($subTeacher);

        $previousTeacherId = $subTeacher->assigned_teacher_id;

        $subTeacher->forceFill($this->assignmentPayload($validated))->save();

        $this->log(
            $previousTeacherId ? 'Updated Sub Teacher Assignment' : 'Assigned Sub Teacher',
            $subTeacher,
            'assigned to ' . ($subTeacher->assignedTeacher->name ?? 'a Teacher')
        );

        return response()->json([
            'success' => true,
            'message' => 'Sub Teacher assigned successfully.',
            'assignment' => $this->assignmentSummary($subTeacher),
        ]);
    }

    public function update(Request $request, User $subTeacher)
    {
        $this->ensureSubTeacher($subTeacher);

        $validated = $request->validate([
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'teacher')->where('status', 'active'),
            ],
            'access_level' => 'required|in:collaborator,view_only,custom',
            'custom_permissions' => 'nullable|array',
            'custom_permissions.*' => 'string|in:' . implode(',', array_keys(CollaborationService::configurablePermissions())),
        ]);

        $previousTeacherId = (int) $subTeacher->assigned_teacher_id;
        $accessLevel = $validated['access_level'];

        $subTeacher->forceFill($this->assignmentPayload($validated))->save();

        $description = (int) $validated['teacher_id'] !== $previousTeacherId
            ? 'reassigned to ' . ($subTeacher->assignedTeacher->name ?? 'a Teacher')
            : 'access level updated to ' . (CollaborationService::accessLevels()[$accessLevel] ?? $accessLevel);

        $this->log('Updated Sub Teacher Assignment', $subTeacher, $description);

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully.',
            'assignment' => $this->assignmentSummary($subTeacher),
        ]);
    }

    public function destroy(User $subTeacher)
    {
        $this->ensureSubTeacher($subTeacher);

        $subTeacher->forceFill([
            'assigned_teacher_id' => null,
            'sub_teacher_access_level' => null,
            'sub_teacher_permissions' => null,
        ])->save();

        $this->log('Unassigned Sub Teacher', $subTeacher, "unassigned; access to the Teacher's records has been revoked");

        return response()->json([
            'success' => true,
            'message' => 'Sub Teacher unassigned. Access has been revoked.',
            'id' => $subTeacher->id,
        ]);
    }

    private function assignmentPayload(array $validated): array
    {
        $accessLevel = $validated['access_level'];

        return [
            'assigned_teacher_id' => $validated['teacher_id'],
            'sub_teacher_access_level' => $accessLevel,
            'sub_teacher_permissions' => $accessLevel === CollaborationService::ACCESS_CUSTOM
                ? ($validated['custom_permissions'] ?? [])
                : null,
        ];
    }

    private function assignmentSummary(User $subTeacher): array
    {
        return [
            'id' => $subTeacher->id,
            'sub_teacher_id' => $subTeacher->id,
            'teacher_id' => $subTeacher->assigned_teacher_id,
            'teacher_name' => $subTeacher->assignedTeacher->name ?? '—',
            'access_level' => $subTeacher->sub_teacher_access_level,
            'status' => $subTeacher->assignmentStatus(),
        ];
    }

    private function validateAssignment(Request $request): array
    {
        return $request->validate([
            'sub_teacher_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'sub_teacher'),
            ],
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'teacher')->where('status', 'active'),
            ],
            'access_level' => 'required|in:collaborator,view_only,custom',
            'custom_permissions' => 'nullable|array',
            'custom_permissions.*' => 'string|in:' . implode(',', array_keys(CollaborationService::configurablePermissions())),
        ]);
    }

    private function ensureSubTeacher(User $user): void
    {
        abort_unless($user->isSubTeacher(), 404, 'Sub Teacher account not found.');
    }

    private function log(string $action, User $subTeacher, string $detail): void
    {
        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => "{$subTeacher->name} {$detail}.",
            'module' => 'Accounts',
        ]);
    }
}
