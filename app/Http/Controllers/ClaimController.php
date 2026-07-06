<?php

namespace App\Http\Controllers;

use App\Models\ClaimItem;
use App\Models\StudentClaim;
use App\Models\Student;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClaimController extends Controller
{
    private function filterStudentsByRole($query)
    {
        if (Auth::user()->isTeacher()) {
            $query->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }
        return $query;
    }

    public function index(Request $request)
    {
        ClaimItem::where('quantity', '<=', 0)
            ->where('status', 'Available')
            ->update(['status' => 'Unavailable']);

        $itemsQuery = ClaimItem::with('creator');

        if ($request->filled('item_search')) {
            $search = $request->item_search;
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'Available') {
                $itemsQuery->where('status', 'Available')->where('quantity', '>', 0);
            } elseif ($request->status === 'Unavailable') {
                $itemsQuery->where(function ($q) {
                    $q->where('status', 'Unavailable')
                      ->orWhere('quantity', '<=', 0);
                });
            }
        }

        if ($request->filled('min_points')) {
            $itemsQuery->where('points_required', '>=', $request->min_points);
        }

        if ($request->filled('max_points')) {
            $itemsQuery->where('points_required', '<=', $request->max_points);
        }

        $items = $itemsQuery->latest()->paginate(15)->withQueryString();

        // Pending claims with role filtering
        $pendingQuery = StudentClaim::with(['student', 'item'])
            ->where('status', 'Pending');

        if (Auth::user()->isTeacher()) {
            $pendingQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        $pendingClaims = $pendingQuery->latest()->paginate(10);

        // Claim history
        $claimsQuery = StudentClaim::with(['student', 'item', 'admin'])
            ->where(function ($q) {
                $q->whereIn('status', ['Approved', 'Rejected', 'Claimed'])
                  ->orWhereNull('status');
            });

        if (Auth::user()->isTeacher()) {
            $claimsQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        if ($request->filled('claim_search')) {
            $search = $request->claim_search;
            $claimsQuery->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($studentQuery) use ($search) {
                      $studentQuery->where('full_name', 'like', "%{$search}%")
                                   ->orWhere('lrn', 'like', "%{$search}%");
                  })
                  ->orWhereHas('admin', function ($adminQuery) use ($search) {
                      $adminQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('claim_student_id')) {
            $claimsQuery->where('student_id', $request->claim_student_id);
        }

        if ($request->filled('claim_item_id')) {
            $claimsQuery->where('claim_item_id', $request->claim_item_id);
        }

        if ($request->filled('date_from')) {
            $claimsQuery->whereDate('claim_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $claimsQuery->whereDate('claim_date', '<=', $request->date_to);
        }

        if ($request->filled('claimed_by')) {
            $claimsQuery->where('claimed_by', $request->claimed_by);
        }

        $claims = $claimsQuery->latest()->paginate(15)->withQueryString();

        $students = Student::query()->whereNotIn('status', ['Archived', 'archived']);
        if (Auth::user()->isTeacher()) {
            $students->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }
        $students = $students->orderBy('first_name')->get();
        $availableItems = ClaimItem::where('status', 'Available')->where('quantity', '>', 0)->get();
        $allClaimItems = ClaimItem::orderBy('item_name')->get();

        return view('pages.claims', compact('items', 'claims', 'pendingClaims', 'students', 'availableItems', 'allClaimItems'));
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:0',
            'status' => 'required|in:Available,Unavailable',
        ]);

        $validated['created_by'] = Auth::id();

        ClaimItem::create($validated);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Added Claim Item',
            'description' => 'Added claim item: ' . $validated['item_name'],
            'module' => 'Claims',
        ]);

        return redirect()->route('claims.index')->with('success', 'Claim item added successfully.');
    }

    public function updateItem(Request $request, ClaimItem $claimItem)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:0',
            'status' => 'required|in:Available,Unavailable',
        ]);

        $claimItem->update($validated);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Updated Claim Item',
            'description' => 'Updated claim item: ' . $validated['item_name'],
            'module' => 'Claims',
        ]);

        return redirect()->route('claims.index')->with('success', 'Claim item updated successfully.');
    }

    public function claimItem(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'claim_item_id' => 'required|exists:claim_items,id',
            'remarks' => 'nullable|string',
        ], [
            'student_id.required' => 'Please select a valid student from the search results.',
            'student_id.exists' => 'The selected student does not exist.',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        if (Auth::user()->isTeacher() && $student->teacher_id !== Auth::id()) {
            abort(403, 'You cannot claim items for students assigned to another teacher.');
        }
        $item = ClaimItem::findOrFail($validated['claim_item_id']);

        if ($item->status !== 'Available') {
            return back()->withErrors(['claim_error' => 'This item is not available.'])->withInput();
        }

        if ($item->quantity <= 0) {
            return back()->withErrors(['claim_error' => 'This item is out of stock.'])->withInput();
        }

        $pointsBefore = $student->total_points ?? 0;
        $pointsRequired = $item->points_required;

        if ($pointsBefore < $pointsRequired) {
            return back()->withErrors([
                'claim_error' => 'Insufficient points. Student does not have enough points to claim this item.'
            ])->withInput();
        }

        $pointsAfter = $pointsBefore - $pointsRequired;

        DB::transaction(function () use ($student, $item, $pointsBefore, $pointsAfter, $pointsRequired, $validated) {
            $student->update(['total_points' => $pointsAfter]);
            $item->decrement('quantity');
            $item->refresh();

            if ($item->quantity <= 0) {
                $item->update(['status' => 'Unavailable']);
            }

            StudentClaim::create([
                'student_id' => $student->id,
                'claim_item_id' => $item->id,
                'item_name' => $item->item_name,
                'points_deducted' => $pointsRequired,
                'points_before' => $pointsBefore,
                'points_after' => $pointsAfter,
                'claim_date' => now()->toDateString(),
                'claimed_by' => Auth::id(),
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'Approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        });

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Claimed Item',
            'description' => "{$student->full_name} claimed {$item->item_name} for {$pointsRequired} points.",
            'module' => 'Claims',
        ]);

        return redirect()->route('claims.index')->with('success', 'Item claimed successfully. Student points have been updated.');
    }

    public function approve(StudentClaim $claim)
    {
        $student = $claim->student;
        $item = $claim->item;

        if (!$student || !$item) {
            return back()->withErrors(['claim' => 'Invalid claim request.']);
        }

        if (in_array(Auth::user()->role, ['teacher', 'Teacher']) && $student->teacher_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($claim->status !== 'Pending') {
            return back()->withErrors(['claim' => 'This claim request is no longer pending.']);
        }

        if ($item->quantity <= 0 || $item->status !== 'Available') {
            return back()->withErrors(['claim' => 'This item is no longer available.']);
        }

        if (($student->total_points ?? 0) < $item->points_required) {
            return back()->withErrors(['claim' => 'Student no longer has enough points.']);
        }

        DB::transaction(function () use ($claim, $student, $item) {
            $pointsBefore = $student->total_points ?? 0;
            $pointsAfter = $pointsBefore - $item->points_required;

            $student->update([
                'total_points' => $pointsAfter,
            ]);

            $item->decrement('quantity');
            $item->refresh();

            if ($item->quantity <= 0) {
                $item->update([
                    'status' => 'Unavailable',
                ]);
            }

            $claim->update([
                'points_deducted' => $item->points_required,
                'points_before' => $pointsBefore,
                'points_after' => $pointsAfter,
                'status' => 'Approved',
                'claimed_by' => Auth::id(),
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        });

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Approved Claim',
            'description' => "Approved {$student->full_name}'s claim for {$claim->item_name}.",
            'module' => 'Claims',
        ]);

        return back()->with('success', 'Item claim approved successfully.');
    }

    public function reject(Request $request, StudentClaim $claim)
    {
        $request->validate([
            'rejected_reason' => 'nullable|string|max:500',
        ]);

        if (in_array(Auth::user()->role, ['teacher', 'Teacher']) && $claim->student->teacher_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($claim->status !== 'Pending') {
            return back()->withErrors(['claim' => 'This claim request is no longer pending.']);
        }

        $claim->update([
            'status' => 'Rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejected_reason' => $request->rejected_reason,
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Rejected Claim',
            'description' => "Rejected {$claim->student->full_name}'s claim for {$claim->item_name}.",
            'module' => 'Claims',
        ]);

        return back()->with('success', 'Item claim rejected successfully.');
    }

    public function filterItems(Request $request)
    {
        $claimItems = ClaimItem::with('creator');

        if ($request->filled('item_search')) {
            $search = $request->item_search;
            $claimItems->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'Available') {
                $claimItems->where('status', 'Available')->where('quantity', '>', 0);
            } elseif ($request->status === 'Unavailable') {
                $claimItems->where(function ($q) {
                    $q->where('status', 'Unavailable')
                      ->orWhere('quantity', '<=', 0);
                });
            }
        }

        if ($request->filled('min_points')) {
            $claimItems->where('points_required', '>=', $request->min_points);
        }

        if ($request->filled('max_points')) {
            $claimItems->where('points_required', '<=', $request->max_points);
        }

        $claimItems = $claimItems->latest()->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('partials.claim-items-table', compact('claimItems'))->render();
        }

        return redirect()->route('claims.index');
    }

    public function filterHistory(Request $request)
    {
        $claims = StudentClaim::with(['student', 'item', 'admin'])
            ->where(function ($q) {
                $q->whereIn('status', ['Approved', 'Rejected', 'Claimed'])
                  ->orWhereNull('status');
            });

        if (Auth::user()->isTeacher()) {
            $claims->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        if ($request->filled('claim_search')) {
            $search = $request->claim_search;
            $claims->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($studentQuery) use ($search) {
                      $studentQuery->where('full_name', 'like', "%{$search}%")
                                   ->orWhere('lrn', 'like', "%{$search}%");
                  })
                  ->orWhereHas('admin', function ($adminQuery) use ($search) {
                      $adminQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('claim_student_id')) {
            $claims->where('student_id', $request->claim_student_id);
        }

        if ($request->filled('claim_item_id')) {
            $claims->where('claim_item_id', $request->claim_item_id);
        }

        if ($request->filled('date_from')) {
            $claims->whereDate('claim_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $claims->whereDate('claim_date', '<=', $request->date_to);
        }

        if ($request->filled('claimed_by')) {
            $claims->where('claimed_by', $request->claimed_by);
        }

        $claims = $claims->latest()->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('partials.claim-history-table', compact('claims'))->render();
        }

        return redirect()->route('claims.index');
    }
}
