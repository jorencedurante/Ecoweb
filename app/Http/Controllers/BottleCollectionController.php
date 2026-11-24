<?php

namespace App\Http\Controllers;

use App\Models\BottleCollection;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BottleCollectionController extends Controller
{
    public function index(Request $request)
    {
        if ($notice = $this->unassignedSubTeacherNotice('Bottle Collection')) {
            return $notice;
        }

        $query = $this->buildFilteredQuery($request);

        $collections = $query->orderBy('collection_date', 'desc')
            ->orderBy('collection_time', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('pages.bottle-collection', compact('collections'));
    }

    public function fetchJson(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        $collections = $query->orderBy('collection_date', 'desc')
            ->orderBy('collection_time', 'desc')
            ->paginate(10)
            ->withQueryString();

        $records = $collections->map(function ($c) {
            return [
                'lrn' => $c->lrn,
                'student' => $c->student->full_name ?? 'Unknown',
                'date' => $c->collection_date?->format('Y-m-d') ?? $c->collection_date,
                'time' => $c->collection_time ? \Carbon\Carbon::parse($c->collection_time)->format('h:i A') : '',
                'bottle_count' => (int) $c->bottle_count,
                'points' => (int) $c->points_earned,
                // Owner is the assigned Teacher; this is the actual actor.
                'recorded_by' => $c->recorder->name ?? 'System / IoT',
            ];
        });

        return response()->json([
            'success' => true,
            'records' => $records,
            'pagination' => [
                'current_page' => $collections->currentPage(),
                'last_page' => $collections->lastPage(),
                'total' => $collections->total(),
                'from' => $collections->firstItem(),
                'to' => $collections->lastItem(),
            ],
        ]);
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = BottleCollection::with('student', 'recorder');

        if (Auth::user()->isSuperAdmin()) {
            // Super Admin sees all records — no filter needed
        } elseif (Auth::user()->isAdmin()) {
            // Admin sees all records (no school/campus scope in this system)
        } elseif (($teacherId = Auth::user()->scopeTeacherId()) !== null) {
            $studentIds = Student::where('status', '!=', 'Archived')
                ->where(function ($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId)
                      ->orWhereHas('enrollments', function ($qq) use ($teacherId) {
                          $qq->where('teacher_id', $teacherId)->where('status', 'active');
                      });
                })
                ->pluck('id');

            $query->whereIn('student_id', $studentIds);
        }

        $search = $request->get('search');
        $search = is_string($search) ? trim($search) : '';

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('lrn', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($studentQuery) use ($search) {
                      $studentQuery->nameSearch($search);
                  });
            });
        }

        $date = $request->get('date');
        $date = is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null;

        if ($date !== null) {
            $query->whereDate('collection_date', $date);
        }

        if ($day = $request->get('day')) {
            $query->whereDay('collection_date', $day);
        }

        if ($month = $request->get('month')) {
            $monthNames = [
                'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4,
                'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8,
                'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12
            ];
            $monthNum = is_numeric($month) ? $month : ($monthNames[$month] ?? null);
            if ($monthNum) {
                $query->whereMonth('collection_date', $monthNum);
            }
        }

        if ($year = $request->get('year')) {
            $query->whereYear('collection_date', $year);
        }

        if ($quarter = $request->get('quarter')) {
            // Quarter is scoped to the selected date's year (or an explicit legacy
            // year param) so it never mixes records from unrelated years. Without a
            // year context it falls back to the existing current-year behavior.
            $yearContext = null;

            if ($date !== null) {
                $yearContext = (int) substr($date, 0, 4);
            } elseif (is_string($year) && $year !== '') {
                $yearContext = (int) $year;
            }

            [$startDate, $endDate] = $this->getQuarterRange($quarter, $yearContext);
            if ($startDate && $endDate) {
                $query->whereBetween('collection_date', [$startDate, $endDate]);
            }
        }

        return $query;
    }

    private function getQuarterRange(string $quarter, ?int $year): array
    {
        $quarterMonths = [
            'q1' => [1, 1, 3, 31],
            'q2' => [4, 1, 6, 30],
            'q3' => [7, 1, 9, 30],
            'q4' => [10, 1, 12, 31],
        ];

        if ($year !== null && isset($quarterMonths[$quarter])) {
            [$startMonth, $startDay, $endMonth, $endDay] = $quarterMonths[$quarter];

            return [
                \Carbon\Carbon::create($year, $startMonth, $startDay)->startOfDay(),
                \Carbon\Carbon::create($year, $endMonth, $endDay)->endOfDay(),
            ];
        }

        return $this->getQuarterDateRange($quarter);
    }

    /**
     * Manual bottle entry was a test-only workflow and is now disabled.
     * Collections and their points come exclusively from the EcoCollect
     * machine through POST /api/bottle-scan (Api\BottleScanController).
     *
     * The named route is kept so older bookmarks resolve to a clear denial
     * instead of a missing-route error; no record or points are created here.
     */
    public function store(Request $request)
    {
        abort(403, 'Manual bottle collection entry is disabled. Collections are recorded by the EcoCollect machine.');
    }

    public function studentPoints(Request $request)
    {
        $lrn = $request->query('lrn');

        if ($lrn === null || $lrn === '') {
            return response()->json([
                'success' => false,
                'message' => 'lrn parameter is required',
            ], 422);
        }

        $student = Student::where('lrn', $lrn)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'student_name' => $student->full_name,
            'lrn' => $student->lrn,
            'points' => (int) ($student->total_points ?? 0),
        ]);
    }
}
