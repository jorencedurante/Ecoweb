<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\ClaimItem;
use App\Models\StudentClaim;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

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
            ->orderBy('item_name')
            ->get();

        return view('public.items', compact('availableItems'));
    }

    public function points(Request $request)
    {
        $validated = $request->validate([
            'lrn' => 'required|string|max:50',
        ]);

        if ($this->pointsLookupLimitExceeded($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again in a minute.',
            ], 429);
        }

        try {
            $student = Student::where('lrn', trim($validated['lrn']))
                ->whereNotIn('status', ['Archived', 'archived'])
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student LRN not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'available_points' => (int) ($student->total_points ?? 0),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to check points. Please try again.',
            ], 500);
        }
    }

    /**
     * Public rate limit for the points lookup (30 requests per IP per minute).
     *
     * Uses the file cache store on purpose: the throttle middleware relies on
     * the database cache table, which does not exist in every environment
     * this project runs against (local + Railway). Rate limiting must never
     * be able to break the lookup itself, so limiter failures fail open.
     */
    private function pointsLookupLimitExceeded(Request $request): bool
    {
        try {
            $cache = Cache::store('file');
            $key = 'items-points-lookup:' . sha1((string) $request->ip());
            $now = time();

            $record = $cache->get($key);

            if (!is_array($record) || (int) ($record['expires_at'] ?? 0) < $now) {
                $record = ['count' => 0, 'expires_at' => $now + 60];
            }

            $record['count']++;

            $cache->put($key, $record, 61);

            return $record['count'] > 30;
        } catch (Throwable $e) {
            return false;
        }
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
            'quantity' => 'required|integer|min:1',
            'request_token' => 'nullable|string|max:64',
        ]);

        $token = $validated['request_token'] ?? null;

        // A retried or double-clicked submission carries the same token as the
        // original request, so it must never reserve anything twice.
        if ($token) {
            $existingClaim = StudentClaim::where('request_token', $token)->first();

            if ($existingClaim) {
                return redirect()
                    ->route('public.items')
                    ->with('success', $this->reservationSuccessMessage($existingClaim))
                    ->withInput(['lrn' => $validated['lrn']]);
            }
        }

        try {
            $result = DB::transaction(function () use ($validated, $token) {
                // Lock ordering is always student first, then item
                // (the approval/rejection flows follow the same order).
                $student = Student::where('lrn', trim($validated['lrn']))
                    ->whereNotIn('status', ['Archived', 'archived'])
                    ->lockForUpdate()
                    ->first();

                if (!$student) {
                    throw ValidationException::withMessages([
                        'lrn' => 'Student record not found.',
                    ]);
                }

                // Same-token resubmission that waited on the student lock.
                if ($token && StudentClaim::where('request_token', $token)->exists()) {
                    return ['duplicate' => true];
                }

                $item = ClaimItem::whereKey($validated['claim_item_id'])->lockForUpdate()->first();

                if (!$item) {
                    throw ValidationException::withMessages([
                        'claim_item_id' => 'This item is currently unavailable.',
                    ]);
                }

                if ($item->status !== 'Available' || $item->quantity <= 0) {
                    throw ValidationException::withMessages([
                        'claim_item_id' => 'This item is currently unavailable.',
                    ]);
                }

                $quantity = (int) $validated['quantity'];

                if ($item->quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Not enough stock available. Only ' . $item->quantity . ' piece(s) left for this item.',
                    ]);
                }

                $totalPointsRequired = (int) $item->points_required * $quantity;
                $pointsBefore = (int) ($student->total_points ?? 0);

                if ($pointsBefore < $totalPointsRequired) {
                    throw ValidationException::withMessages([
                        'claim_item_id' => 'Insufficient points. You need ' . number_format($totalPointsRequired) . ' points for this request.',
                    ]);
                }

                $existingPending = StudentClaim::where('student_id', $student->id)
                    ->where('claim_item_id', $item->id)
                    ->where('status', 'Pending')
                    ->exists();

                if ($existingPending) {
                    throw ValidationException::withMessages([
                        'claim_item_id' => 'You already have a pending request for this item. Please wait for it to be approved or rejected before submitting another.',
                    ]);
                }

                $pointsAfter = $pointsBefore - $totalPointsRequired;
                $remainingStock = (int) $item->quantity - $quantity;

                // Conditional atomic updates: the balance can never go below
                // zero and the stock can never go negative, even under
                // concurrent requests.
                $pointsReserved = Student::whereKey($student->id)
                    ->where('total_points', '>=', $totalPointsRequired)
                    ->update(['total_points' => $pointsAfter]);

                if ($pointsReserved === 0) {
                    throw ValidationException::withMessages([
                        'claim_item_id' => 'Insufficient points. You need ' . number_format($totalPointsRequired) . ' points for this request.',
                    ]);
                }

                $stockReserved = ClaimItem::whereKey($item->id)
                    ->where('quantity', '>=', $quantity)
                    ->update([
                        'quantity' => $remainingStock,
                        'status' => $remainingStock <= 0 ? 'Unavailable' : 'Available',
                    ]);

                if ($stockReserved === 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Not enough stock available. Only ' . $item->quantity . ' piece(s) left for this item.',
                    ]);
                }

                $claim = StudentClaim::create([
                    'student_id' => $student->id,
                    'claim_item_id' => $item->id,
                    'quantity' => $quantity,
                    'item_name' => $item->item_name,
                    'points_deducted' => $totalPointsRequired,
                    'points_before' => $pointsBefore,
                    'points_after' => $pointsAfter,
                    'claim_date' => now()->toDateString(),
                    'claimed_by' => null,
                    'remarks' => 'Requested from public Items page',
                    'status' => 'Pending',
                    'is_reserved' => true,
                    'request_token' => $token,
                ]);

                return [
                    'duplicate' => false,
                    'claim' => $claim,
                ];
            });
        } catch (ValidationException $e) {
            return redirect()
                ->route('public.items')
                ->withErrors($e->errors())
                ->withInput();
        } catch (QueryException $e) {
            // Unique request_token collision: a truly parallel duplicate lost
            // the race, so its whole transaction (including any deduction)
            // was rolled back. Report the original submission instead.
            if ($token && str_contains($e->getMessage(), 'request_token')) {
                $existingClaim = StudentClaim::where('request_token', $token)->first();

                if ($existingClaim) {
                    return redirect()
                        ->route('public.items')
                        ->with('success', $this->reservationSuccessMessage($existingClaim))
                        ->withInput(['lrn' => $validated['lrn']]);
                }
            }

            report($e);

            return redirect()
                ->route('public.items')
                ->withErrors(['claim_item_id' => 'Unable to submit your claim request. Please try again.'])
                ->withInput();
        }

        if (!empty($result['duplicate'])) {
            $existingClaim = StudentClaim::where('request_token', $token)->first();

            return redirect()
                ->route('public.items')
                ->with('success', $this->reservationSuccessMessage($existingClaim))
                ->withInput(['lrn' => $validated['lrn']]);
        }

        return redirect()
            ->route('public.items')
            ->with('success', $this->reservationSuccessMessage($result['claim']))
            ->withInput(['lrn' => $validated['lrn']]);
    }

    private function reservationSuccessMessage(?StudentClaim $claim): string
    {
        $points = (int) ($claim->points_deducted ?? 0);
        $quantity = (int) ($claim->quantity ?? 1);

        return 'Claim request submitted! '
            . number_format($points) . ' point' . ($points === 1 ? '' : 's')
            . ' and ' . $quantity . ' item piece' . ($quantity === 1 ? ' has' : 's have')
            . ' been reserved while awaiting admin approval.';
    }

    public function studentDetails($lrn)
    {
        $lrn = $this->extractLrnFromQrValue($lrn) ?? $lrn;

        $student = Student::where('lrn', $lrn)
            ->where('status', '!=', 'Archived')
            ->firstOrFail();

        $student->syncEarnedAchievements();

        $student->load([
            'earnedAchievements.quest',
            'certificateAwards',
            'bottleCollections',
        ]);

        $achievementProgress = $student->achievementProgressData();

        return view('landing-student-details', compact('student', 'achievementProgress'));
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
            ->withCount(['bottleCollections as quarter_bottles' => function ($query) use ($currentQuarterStart, $currentQuarterEnd) {
                $query->whereBetween('collection_date', [$currentQuarterStart, $currentQuarterEnd]);
            }])
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
            ->withCount(['bottleCollections as prev_bottles' => function ($query) use ($previousQuarterStart, $previousQuarterEnd) {
                $query->whereBetween('collection_date', [$previousQuarterStart, $previousQuarterEnd]);
            }])
            ->where('status', '!=', 'Archived')
            ->orderByDesc('prev_points')
            ->orderByDesc('prev_bottles')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(10)
            ->get();

        if ($currentQuarterTopStudents->isEmpty()) {
            $currentQuarterTopStudents = Student::query()
                ->withCount('bottleCollections as total_bottles_collected')
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
