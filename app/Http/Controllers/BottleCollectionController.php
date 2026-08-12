<?php

namespace App\Http\Controllers;

use App\Models\BottleCollection;
use App\Models\Student;
use App\Models\Achievement;
use App\Models\StudentAchievement;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BottleCollectionController extends Controller
{
    public function index(Request $request)
    {
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
        $query = BottleCollection::with('student');

        if (Auth::user()->isSuperAdmin()) {
            // Super Admin sees all records — no filter needed
        } elseif (Auth::user()->isAdmin()) {
            // Admin sees all records (no school/campus scope in this system)
        } elseif (Auth::user()->isTeacher()) {
            $studentIds = Student::where('teacher_id', Auth::id())
                ->orWhereHas('enrollments', function ($q) {
                    $q->where('teacher_id', Auth::id())->where('status', 'active');
                })
                ->pluck('id');

            if ($studentIds->isNotEmpty()) {
                $query->whereIn('student_id', $studentIds);
            } else {
                $query->where('created_by', Auth::id());
            }
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lrn', 'like', "%{$search}%")
                  ->orWhere('bottle_count', 'like', "%{$search}%")
                  ->orWhereDate('collection_date', $search)
                  ->orWhereHas('student', function ($studentQuery) use ($search) {
                      $studentQuery->where('full_name', 'like', "%{$search}%")
                                   ->orWhere('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
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
            [$startDate, $endDate] = $this->getQuarterDateRange($quarter);
            if ($startDate && $endDate) {
                $query->whereBetween('collection_date', [$startDate, $endDate]);
            }
        }

        return $query;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'collection_date' => 'required|date',
            'collection_time' => 'required',
            'bottle_count' => 'required|integer|min:1',
        ]);

        $student = Student::findOrFail($validated['student_id']);

        if (Auth::user()->isTeacher()) {
            $hasAccess = $student->enrollments()
                    ->where('teacher_id', Auth::id())
                    ->where('status', 'active')
                    ->exists()
                || $student->teacher_id === Auth::id();

            if (!$hasAccess) {
                abort(403, 'You cannot add bottle collections for students assigned to another teacher.');
            }
        }

        $validated['lrn'] = $student->lrn;
        $validated['points_earned'] = $validated['bottle_count'];
        $validated['created_by'] = Auth::id();

        BottleCollection::create($validated);

        // Update student total points: 1 bottle = 1 point
        $student->increment('total_points', $validated['bottle_count']);

        // Auto-award achievement quests
        $bottleTotal = $student->bottleCollections()->sum('bottle_count');
        $pointTotal = $student->total_points ?? $bottleTotal;
        $quests = Achievement::whereNull('student_id')->where('status', 'Active')->get();
        foreach ($quests as $quest) {
            $completedByBottles = $quest->required_bottles > 0 && $bottleTotal >= $quest->required_bottles;
            $completedByPoints = $quest->points_required > 0 && $pointTotal >= $quest->points_required;
            if ($completedByBottles || $completedByPoints) {
                StudentAchievement::firstOrCreate(
                    ['student_id' => $student->id, 'achievement_quest_id' => $quest->id],
                    ['awarded_date' => now()->toDateString(), 'awarded_by' => Auth::id()]
                );
            }
        }

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Added Bottle Collection',
            'description' => "Recorded {$validated['bottle_count']} bottles for {$student->full_name}.",
            'module' => 'Bottle Collection',
        ]);

        return redirect()->route('admin.bottle-collection')->with('success', 'Bottle collection recorded successfully!');
    }
}
