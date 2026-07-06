<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\ClaimItem;
use App\Models\StudentClaim;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function landing()
    {
        [$currentQuarterTopStudents, $previousQuarterTopStudents] = $this->getLandingRankings();

        return view('public.landing', compact(
            'currentQuarterTopStudents',
            'previousQuarterTopStudents'
        ));
    }

    public function items()
    {
        $availableItems = ClaimItem::where('status', 'Available')
            ->where('quantity', '>', 0)
            ->orderBy('item_name')
            ->get();

        return view('public.items', compact('availableItems'));
    }

    public function studentLookup(Request $request)
    {
        $request->validate([
            'lrn' => 'required|string|max:200',
        ]);

        $lrn = $this->extractLrnFromQrValue($request->lrn);

        if (!$lrn) {
            return back()->withErrors([
                'lrn' => 'No student record found for this LRN.',
            ])->withInput();
        }

        $student = Student::with([
            'earnedAchievements.quest',
            'certificateAwards',
            'bottleCollections',
        ])
        ->where('lrn', $lrn)
        ->where('status', '!=', 'Archived')
        ->first();

        if (!$student) {
            return back()->withErrors([
                'lrn' => 'No student record found for LRN: ' . $lrn,
            ])->withInput();
        }

        [$currentQuarterTopStudents, $previousQuarterTopStudents] = $this->getLandingRankings();

        return view('public.landing', compact(
            'student',
            'currentQuarterTopStudents',
            'previousQuarterTopStudents'
        ));
    }

    public function requestItemClaim(Request $request)
    {
        $validated = $request->validate([
            'lrn' => 'required|string',
            'claim_item_id' => 'required|exists:claim_items,id',
        ]);

        $student = Student::where('lrn', $validated['lrn'])
            ->whereNotIn('status', ['Archived', 'archived'])
            ->first();

        if (!$student) {
            return redirect()
                ->route('public.items')
                ->withErrors(['lrn' => 'Student record not found.'])
                ->withInput();
        }

        $item = ClaimItem::findOrFail($validated['claim_item_id']);

        if ($item->status !== 'Available' || $item->quantity <= 0) {
            return redirect()
                ->route('public.items')
                ->withErrors(['claim_item_id' => 'This item is currently unavailable.'])
                ->withInput();
        }

        if (($student->total_points ?? 0) < $item->points_required) {
            return redirect()
                ->route('public.items')
                ->withErrors(['claim_item_id' => 'You do not have enough points to request this item.'])
                ->withInput();
        }

        $existingPending = StudentClaim::where('student_id', $student->id)
            ->where('claim_item_id', $item->id)
            ->where('status', 'Pending')
            ->exists();

        if ($existingPending) {
            return redirect()
                ->route('public.items')
                ->withErrors(['claim_item_id' => 'You already have a pending request for this item.'])
                ->withInput();
        }

        StudentClaim::create([
            'student_id' => $student->id,
            'claim_item_id' => $item->id,
            'item_name' => $item->item_name,
            'points_deducted' => $item->points_required,
            'points_before' => $student->total_points ?? 0,
            'points_after' => ($student->total_points ?? 0) - $item->points_required,
            'claim_date' => now()->toDateString(),
            'claimed_by' => null,
            'remarks' => 'Requested from public Items page',
            'status' => 'Pending',
        ]);

        return redirect()
            ->route('public.items')
            ->with('success', 'Your item claim request has been submitted and is pending approval.');
    }

    public function studentDetails($lrn)
    {
        $lrn = $this->extractLrnFromQrValue($lrn) ?? $lrn;

        $student = Student::where('lrn', $lrn)
            ->with([
                'earnedAchievements.quest',
                'certificateAwards',
                'bottleCollections',
            ])
            ->where('status', '!=', 'Archived')
            ->firstOrFail();

        return view('landing-student-details', compact('student'));
    }

    private function getLandingRankings(): array
    {
        $now = now();

        $currentQuarterStart = $now->copy()->firstOfQuarter()->startOfDay();
        $currentQuarterEnd = $now->copy()->lastOfQuarter()->endOfDay();

        $previousQuarterDate = $now->copy()->subQuarter();
        $previousQuarterStart = $previousQuarterDate->copy()->firstOfQuarter()->startOfDay();
        $previousQuarterEnd = $previousQuarterDate->copy()->lastOfQuarter()->endOfDay();

        $currentQuarterTopStudents = Student::query()
            ->withSum(['bottleCollections as quarter_points' => function ($query) use ($currentQuarterStart, $currentQuarterEnd) {
                $query->whereBetween('collection_date', [$currentQuarterStart, $currentQuarterEnd]);
            }], 'points_earned')
            ->withSum(['bottleCollections as quarter_bottles' => function ($query) use ($currentQuarterStart, $currentQuarterEnd) {
                $query->whereBetween('collection_date', [$currentQuarterStart, $currentQuarterEnd]);
            }], 'bottle_count')
            ->where('status', '!=', 'Archived')
            ->orderByDesc('quarter_points')
            ->orderByDesc('quarter_bottles')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(10)
            ->get();

        $previousQuarterTopStudents = Student::query()
            ->withSum(['bottleCollections as prev_points' => function ($query) use ($previousQuarterStart, $previousQuarterEnd) {
                $query->whereBetween('collection_date', [$previousQuarterStart, $previousQuarterEnd]);
            }], 'points_earned')
            ->withSum(['bottleCollections as prev_bottles' => function ($query) use ($previousQuarterStart, $previousQuarterEnd) {
                $query->whereBetween('collection_date', [$previousQuarterStart, $previousQuarterEnd]);
            }], 'bottle_count')
            ->where('status', '!=', 'Archived')
            ->orderByDesc('prev_points')
            ->orderByDesc('prev_bottles')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(10)
            ->get();

        if ($currentQuarterTopStudents->isEmpty()) {
            $currentQuarterTopStudents = Student::query()
                ->withSum('bottleCollections as total_bottles_collected', 'bottle_count')
                ->where('status', '!=', 'Archived')
                ->orderByDesc('total_points')
                ->orderByDesc('total_bottles_collected')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(10)
                ->get()
                ->map(function ($student) {
                    $student->quarter_points = $student->total_points ?? 0;
                    $student->quarter_bottles = $student->total_bottles_collected ?? 0;
                    return $student;
                });
        }

        return [$currentQuarterTopStudents, $previousQuarterTopStudents];
    }

    private function extractLrnFromQrValue(?string $qrValue): ?string
    {
        $qrValue = trim((string) $qrValue);

        if ($qrValue === '') {
            return null;
        }

        if (preg_match('/LRN:\s*([0-9]+)/i', $qrValue, $matches)) {
            return $matches[1];
        }

        if (preg_match('/([0-9]{10,20})/', $qrValue, $matches)) {
            return $matches[1];
        }

        return preg_replace('/[^0-9]/', '', $qrValue);
    }
}
