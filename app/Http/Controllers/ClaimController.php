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
    public function index()
    {
        $items = ClaimItem::with('creator')->latest()->paginate(15);
        $claims = StudentClaim::with(['student', 'item', 'admin'])->latest()->paginate(15);
        $students = Student::where('status', '!=', 'Archived')->orderBy('first_name')->get();

        return view('pages.claims', compact('items', 'claims', 'students'));
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

    public function claimItem(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'claim_item_id' => 'required|exists:claim_items,id',
            'remarks' => 'nullable|string',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $item = ClaimItem::findOrFail($validated['claim_item_id']);

        if ($item->status !== 'Available') {
            return back()->withErrors(['claim_item_id' => 'This item is not available.'])->withInput();
        }

        if ($item->quantity <= 0) {
            return back()->withErrors(['claim_item_id' => 'This item is out of stock.'])->withInput();
        }

        $pointsBefore = $student->total_points ?? 0;
        $pointsRequired = $item->points_required;

        if ($pointsBefore < $pointsRequired) {
            return back()->withErrors([
                'student_id' => 'Insufficient points. Student does not have enough points to claim this item.'
            ])->withInput();
        }

        $pointsAfter = $pointsBefore - $pointsRequired;

        DB::transaction(function () use ($student, $item, $pointsBefore, $pointsAfter, $pointsRequired, $validated) {
            $student->update(['total_points' => $pointsAfter]);
            $item->decrement('quantity');

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

    public function history()
    {
        $claims = StudentClaim::with(['student', 'item', 'admin'])->latest()->paginate(20);
        return view('pages.claims-history', compact('claims'));
    }
}
