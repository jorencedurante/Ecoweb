<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentClaim;
use App\Models\BottleCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = now();
        $currentYear = $now->year;

        [$currentQuarterStart, $currentQuarterEnd] = $this->getQuarterDateRange('current');

        $studentsBase = Student::whereNotIn('status', ['Archived', 'archived']);
        $bottlesBase = BottleCollection::query();
        $awardsBase = \App\Models\CertificateAward::query();

        if ($user->isTeacher()) {
            $studentsBase->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', $user->id)->where('status', 'active');
            });
            $bottlesBase->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', $user->id)->where('status', 'active');
            });
            $awardsBase->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', $user->id)->where('status', 'active');
            });
        }

        $totalStudents = (clone $studentsBase)->count();
        $totalBottles = (clone $bottlesBase)->sum('bottle_count');
        $totalAwards = (clone $awardsBase)->count();

        // --- Gender Contribution (Admin / Super Admin only) ---
        // Count UNIQUE students with at least one bottle collection this quarter,
        // grouped by gender. Percentages based on contributing students only.
        $genderContribution = null;
        if ($user->isAdminLevel()) {
            $contributingStudents = (clone $studentsBase)
                ->whereHas('bottleCollections', function ($q) use ($currentQuarterStart, $currentQuarterEnd) {
                    $q->whereBetween('collection_date', [$currentQuarterStart, $currentQuarterEnd]);
                });
            $femaleContributors = (clone $contributingStudents)->where('gender', 'Female')->count();
            $maleContributors = (clone $contributingStudents)->where('gender', 'Male')->count();
            $totalContributors = $femaleContributors + $maleContributors;
            $genderContribution = [
                'female' => $femaleContributors,
                'male' => $maleContributors,
                'total' => $totalContributors,
                'femalePct' => $totalContributors > 0 ? round($femaleContributors / $totalContributors * 100, 2) : 0,
                'malePct' => $totalContributors > 0 ? round($maleContributors / $totalContributors * 100, 2) : 0,
            ];
        }

        // --- Quarterly Bottle Collections (Admin / Super Admin only) ---
        // Sum of actual bottle quantities per calendar quarter, current year only.
        $quarterlyCollections = [];
        if ($user->isAdminLevel()) {
            for ($q = 1; $q <= 4; $q++) {
                $quarterStart = \Carbon\Carbon::create($currentYear, ($q - 1) * 3 + 1, 1)->startOfDay();
                $quarterEnd = \Carbon\Carbon::create($currentYear, $q * 3, 1)->startOfDay()->addMonth(1)->subDay()->endOfDay();
                $bottleSum = (clone $bottlesBase)->whereBetween('collection_date', [$quarterStart, $quarterEnd])->sum('bottle_count');
                $quarterlyCollections[] = [
                    'quarter' => 'Q' . $q,
                    'bottles' => (int) $bottleSum,
                ];
            }
        }

        // --- Top Students — Quarterly Ranking ---
        // Rank by bottles collected in the CURRENT calendar quarter only.
        // Exclude students with zero bottles this quarter. Max 10 (3 for teachers).
        $topStudents = (clone $studentsBase)
            ->withSum(['bottleCollections as quarter_bottles' => function ($q) use ($currentQuarterStart, $currentQuarterEnd) {
                $q->whereBetween('collection_date', [$currentQuarterStart, $currentQuarterEnd]);
            }], 'bottle_count')
            ->having('quarter_bottles', '>', 0)
            ->orderByDesc('quarter_bottles')
            ->orderBy('full_name', 'asc')
            ->take($user->isTeacher() ? 3 : 10)
            ->get();

        return view('pages.reports', compact(
            'totalStudents', 'totalBottles', 'totalAwards',
            'genderContribution', 'quarterlyCollections', 'topStudents',
            'currentQuarterStart', 'currentQuarterEnd'
        ));
    }

    public function studentReport(Request $request)
    {
        if (Auth::user()->isTeacher()) {
            return $this->teacherStudentRanking($request);
        }

        $baseQuery = Student::whereNotIn('status', ['Archived', 'archived']);
        $baseQuery->visibleTo(Auth::user());

        if ($gradeLevel = $request->get('grade_level')) {
            $baseQuery->where('grade_level', $gradeLevel);
        }

        if ($gender = $request->get('gender')) {
            $baseQuery->where('gender', $gender);
        }

        if ($search = $request->get('search')) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('student_id', 'like', "%{$search}%")
                  ->orWhere('lrn', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('grade_level', 'like', "%{$search}%")
                  ->orWhere('gender', 'like', "%{$search}%");
            });
        }

        $totalStudents = (clone $baseQuery)->count();
        $femaleCount = (clone $baseQuery)->where('gender', 'Female')->count();
        $maleCount = (clone $baseQuery)->where('gender', 'Male')->count();

        $baseQuery->withSum('bottleCollections as bottles_collected', 'bottle_count')
            ->withCount('claims as total_claims');

        $students = $baseQuery->orderBy('first_name')->orderBy('last_name')->paginate(10)->withQueryString();
        $gradeLevels = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];

        // For AJAX live-filter requests, return JSON with summary counts + table HTML
        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $tableHtml = view('pages.student-report', compact(
                'students', 'totalStudents', 'femaleCount', 'maleCount', 'gradeLevels'
            ))->render();

            return response()->json([
                'success' => true,
                'total_students' => $totalStudents,
                'female_count' => $femaleCount,
                'male_count' => $maleCount,
                'table_html' => $tableHtml,
            ]);
        }

        return view('pages.student-report', compact(
            'students', 'totalStudents', 'femaleCount', 'maleCount', 'gradeLevels'
        ));
    }

    public function studentReportDetails(Request $request, $id)
    {
        if (Auth::user()->isTeacher()) {
            $student = Student::whereNotIn('status', ['Archived', 'archived'])
                ->whereHas('enrollments', function ($q) {
                    $q->where('teacher_id', Auth::id())->where('status', 'active');
                })
                ->where('id', $id)
                ->first();
        } else {
            $student = Student::whereNotIn('status', ['Archived', 'archived'])
                ->visibleTo(Auth::user())
                ->where('id', $id)
                ->first();
        }

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found or unauthorized.'], 404);
        }

        // Eager load relationships
        $student->load('bottleCollections', 'claims', 'earnedAchievements.quest', 'certificateAwards');

        // Bottle collection history (newest first)
        $bottleCollections = $student->bottleCollections->sortByDesc('collection_date');

        // Total bottles and points
        $totalBottles = $bottleCollections->sum('bottle_count');
        $totalPoints = $student->total_points ?? 0;

        // Claims / rewards
        $claims = $student->claims->where('is_archived', false)->where('status', 'approved');
        $pointsRedeemed = $claims->sum('points_deducted');
        $availablePoints = max(0, $totalPoints - $pointsRedeemed);

        // Achievements
        $achievements = $student->earnedAchievements->sortByDesc('awarded_date');

        // Certificates
        $certificates = $student->certificateAwards->sortByDesc('awarded_date');

        // Enrollment info (teacher, section)
        $enrollment = $student->enrollments->first(function ($e) {
            return $e->status === 'active';
        });
        $teacher = $enrollment?->teacher;
        $section = $enrollment->section ?? 'N/A';

        return response()->json([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'student_id' => $student->student_id ?? 'N/A',
                'lrn' => $student->lrn,
                'full_name' => $student->full_name,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'gender' => $student->gender ?? 'N/A',
                'grade_level' => $student->grade_level ?? 'N/A',
                'section' => $section,
                'birth_date' => $student->birth_date ? $student->birth_date->format('F j, Y') : 'N/A',
                'teacher_name' => $teacher ? $teacher->name : 'N/A',
                'total_bottles' => $totalBottles,
                'total_points' => $totalPoints,
                'points_redeemed' => $pointsRedeemed,
                'available_points' => $availablePoints,
                'achievements_count' => $achievements->count(),
                'certificates_count' => $certificates->count(),
                'bottle_collections' => $bottleCollections->map(function ($c) {
                    return [
                        'collection_date' => $c->collection_date ? $c->collection_date->format('M d, Y') : 'N/A',
                        'collection_time' => $c->collection_time ?? '—',
                        'bottle_count' => $c->bottle_count ?? 0,
                        'points_earned' => $c->points_earned ?? 0,
                    ];
                }),
                'claims' => $claims->map(function ($c) {
                    return [
                        'item_name' => $c->item_name ?? 'N/A',
                        'quantity' => $c->quantity ?? 0,
                        'points_deducted' => $c->points_deducted ?? 0,
                        'claim_date' => $c->claim_date ? $c->claim_date->format('M d, Y') : 'N/A',
                        'status' => $c->status ?? 'N/A',
                    ];
                }),
                'achievements' => $achievements->map(function ($a) {
                    return [
                        'title' => $a->quest?->title ?? 'N/A',
                        'description' => $a->quest?->description ?? 'N/A',
                        'badge_name' => $a->quest?->badge_name ?? 'N/A',
                        'awarded_date' => $a->awarded_date ? $a->awarded_date->format('M d, Y') : 'N/A',
                    ];
                }),
                'certificates' => $certificates->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'award_title' => $c->award_title ?? 'N/A',
                        'award_description' => $c->award_description ?? 'N/A',
                        'awarded_date' => $c->awarded_date ? $c->awarded_date->format('M d, Y') : 'N/A',
                        'status' => $c->status ?? 'N/A',
                        'has_file' => $c->hasFinalCertificateFile(),
                        'can_render' => $c->canRenderCertificate(),
                    ];
                }),
            ],
        ]);
    }

    public function printStudentReport(Request $request, $id)
    {
        if (Auth::user()->isTeacher()) {
            $student = Student::whereNotIn('status', ['Archived', 'archived'])
                ->whereHas('enrollments', function ($q) {
                    $q->where('teacher_id', Auth::id())->where('status', 'active');
                })
                ->where('id', $id)
                ->first();
        } else {
            $student = Student::whereNotIn('status', ['Archived', 'archived'])
                ->visibleTo(Auth::user())
                ->where('id', $id)
                ->first();
        }

        if (!$student) {
            abort(404, 'Student not found or unauthorized.');
        }

        $student->load('bottleCollections', 'claims', 'earnedAchievements.quest', 'certificateAwards', 'enrollments.teacher');

        $bottleCollections = $student->bottleCollections->sortByDesc('collection_date');
        $totalBottles = $bottleCollections->sum('bottle_count');
        $totalPoints = $student->total_points ?? 0;

        $claims = $student->claims->where('is_archived', false)->where('status', 'approved');
        $pointsRedeemed = $claims->sum('points_deducted');
        $availablePoints = max(0, $totalPoints - $pointsRedeemed);

        $achievements = $student->earnedAchievements->sortByDesc('awarded_date');
        $certificates = $student->certificateAwards->sortByDesc('awarded_date');

        $enrollment = $student->enrollments->first(function ($e) {
            return $e->status === 'active';
        });

        return view('pages.reports.print.student-performance', [
            'student' => $student,
            'enrollment' => $enrollment,
            'bottleCollections' => $bottleCollections,
            'totalBottles' => $totalBottles,
            'totalPoints' => $totalPoints,
            'pointsRedeemed' => $pointsRedeemed,
            'availablePoints' => $availablePoints,
            'claims' => $claims,
            'achievements' => $achievements,
            'certificates' => $certificates,
            'isTeacher' => Auth::user()->isTeacher(),
        ]);
    }

    private function teacherStudentRanking(Request $request)
    {
        $query = Student::whereNotIn('status', ['Archived', 'archived'])
            ->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lrn', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $students = $query
            ->with('enrollments')
            ->withSum('bottleCollections as total_bottles', 'bottle_count')
            ->withCount('claims as total_claims')
            ->withMax('bottleCollections as latest_collection_date', 'collection_date')
            ->orderByDesc('total_points')
            ->orderByDesc('total_bottles')
            ->paginate(15)
            ->withQueryString();

        return view('pages.student-report', compact('students'));
    }

    public function bottleReport(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $quarter = $request->get('quarter');
        $search = $request->get('search');

        $baseQuery = BottleCollection::with('student');

        if (Auth::user()->isTeacher()) {
            $baseQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        if ($dateFrom) {
            $baseQuery->whereDate('collection_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $baseQuery->whereDate('collection_date', '<=', $dateTo);
        }
        if ($quarter) {
            [$startDate, $endDate] = $this->getQuarterDateRange($quarter);
            if ($startDate && $endDate) {
                $baseQuery->whereBetween('collection_date', [$startDate, $endDate]);
            }
        }

        // Get distinct students with their collections, paginated by student (10 per page)
        $studentIds = $baseQuery->distinct()->pluck('student_id');

        $studentsQuery = Student::whereIn('id', $studentIds)
            ->whereNotIn('status', ['Archived', 'archived']);

        if (Auth::user()->isTeacher()) {
            $studentsQuery->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        if ($search) {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('lrn', 'like', "%{$search}%");
            });
        }

        $studentGroups = $studentsQuery
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('lrn')
            ->paginate(10)
            ->withQueryString();

        $studentGroups->getCollection()->transform(function ($student) use ($baseQuery, $dateFrom, $dateTo, $quarter) {
            $collections = clone $baseQuery;
            $collections->where('student_id', $student->id);
            if ($dateFrom) {
                $collections->whereDate('collection_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $collections->whereDate('collection_date', '<=', $dateTo);
            }
            if ($quarter) {
                [$startDate, $endDate] = $this->getQuarterDateRange($quarter);
                if ($startDate && $endDate) {
                    $collections->whereBetween('collection_date', [$startDate, $endDate]);
                }
            }
            $studentCollections = $collections->orderBy('collection_date', 'desc')->orderBy('collection_time', 'desc')->get();
            return [
                'student' => $student,
                'collections' => $studentCollections,
                'total_bottles' => $studentCollections->sum('bottle_count'),
            ];
        });

        // Summary statistics — independent of quarter filter (Task 6)
        $summaryQuery = BottleCollection::query();

        if (Auth::user()->isTeacher()) {
            $summaryQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        if ($dateFrom) {
            $summaryQuery->whereDate('collection_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $summaryQuery->whereDate('collection_date', '<=', $dateTo);
        }

        $dailyTotal = (clone $summaryQuery)->whereDate('collection_date', today())->sum('bottle_count');
        $weeklyTotal = (clone $summaryQuery)->whereBetween('collection_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('bottle_count');
        $monthlyTotal = (clone $summaryQuery)->whereMonth('collection_date', now()->month)
            ->whereYear('collection_date', now()->year)
            ->sum('bottle_count');
        $totalBottles = (clone $summaryQuery)->sum('bottle_count');

        // Chart data - last 7 days
        $trendQuery = BottleCollection::selectRaw('DAYNAME(collection_date) as day, SUM(bottle_count) as total')
            ->where('collection_date', '>=', now()->subDays(6));
        if (Auth::user()->isTeacher()) {
            $trendQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }
        if ($dateFrom) {
            $trendQuery->whereDate('collection_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $trendQuery->whereDate('collection_date', '<=', $dateTo);
        }
        if ($quarter) {
            [$s, $e] = $this->getQuarterDateRange($quarter);
            if ($s && $e) {
                $trendQuery->whereBetween('collection_date', [$s, $e]);
            }
        }
        $trendData = $trendQuery
            ->groupBy('day', 'collection_date')
            ->orderBy('collection_date')
            ->get()
            ->keyBy('day');
        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        $chartData = [];
        foreach ($days as $day) {
            $chartData[$day] = $trendData[$day]->total ?? 0;
        }

        return view('pages.bottle-report', compact(
            'studentGroups', 'dailyTotal', 'weeklyTotal', 'monthlyTotal', 'totalBottles', 'chartData',
            'dateFrom', 'dateTo', 'quarter', 'search'
        ));
    }

    public function bottleReportSearch(Request $request)
    {
        $search = $request->get('search', '');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $quarter = $request->get('quarter');

        $baseQuery = BottleCollection::with('student');

        if (Auth::user()->isTeacher()) {
            $baseQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        if ($dateFrom) {
            $baseQuery->whereDate('collection_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $baseQuery->whereDate('collection_date', '<=', $dateTo);
        }
        if ($quarter) {
            [$startDate, $endDate] = $this->getQuarterDateRange($quarter);
            if ($startDate && $endDate) {
                $baseQuery->whereBetween('collection_date', [$startDate, $endDate]);
            }
        }

        $studentIds = $baseQuery->distinct()->pluck('student_id');

        $studentsQuery = Student::whereIn('id', $studentIds)
            ->whereNotIn('status', ['Archived', 'archived']);

        if (Auth::user()->isTeacher()) {
            $studentsQuery->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        if ($search) {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('lrn', 'like', "%{$search}%");
            });
        }

        $page = $request->get('page', 1);

        $students = $studentsQuery
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('lrn')
            ->paginate(10, ['*'], 'page', $page);

        $groups = $students->getCollection()->map(function ($student) use ($baseQuery, $dateFrom, $dateTo, $quarter) {
            $collections = clone $baseQuery;
            $collections->where('student_id', $student->id);
            if ($dateFrom) {
                $collections->whereDate('collection_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $collections->whereDate('collection_date', '<=', $dateTo);
            }
            if ($quarter) {
                [$startDate, $endDate] = $this->getQuarterDateRange($quarter);
                if ($startDate && $endDate) {
                    $collections->whereBetween('collection_date', [$startDate, $endDate]);
                }
            }
            $studentCollections = $collections->orderBy('collection_date', 'desc')->orderBy('collection_time', 'desc')->get();
            return [
                'student' => [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'lrn' => $student->lrn,
                ],
                'collections' => $studentCollections->map(function ($c) {
                    return [
                        'collection_date' => $c->collection_date ? \Carbon\Carbon::parse($c->collection_date)->format('M d, Y') : '—',
                        'collection_time' => $c->collection_time ?: '—',
                        'bottle_count' => $c->bottle_count,
                    ];
                }),
                'total_bottles' => $studentCollections->sum('bottle_count'),
            ];
        })->values()->all();

        // Chart data - last 7 days within filtered period
        $trendQuery = BottleCollection::selectRaw('DAYNAME(collection_date) as day, SUM(bottle_count) as total')
            ->where('collection_date', '>=', now()->subDays(6));
        if (Auth::user()->isTeacher()) {
            $trendQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }
        if ($dateFrom) {
            $trendQuery->whereDate('collection_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $trendQuery->whereDate('collection_date', '<=', $dateTo);
        }
        if ($quarter) {
            [$s, $e] = $this->getQuarterDateRange($quarter);
            if ($s && $e) {
                $trendQuery->whereBetween('collection_date', [$s, $e]);
            }
        }
        $trendData = $trendQuery
            ->groupBy('day', 'collection_date')
            ->orderBy('collection_date')
            ->get()
            ->keyBy('day');
        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        $chartData = [];
        foreach ($days as $day) {
            $chartData[$day] = $trendData[$day]->total ?? 0;
        }

        return response()->json([
            'student_groups' => $groups,
            'pagination' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'from' => $students->isEmpty() ? 0 : $students->firstItem(),
                'to' => $students->isEmpty() ? 0 : $students->lastItem(),
                'total' => $students->total(),
            ],
            'chart_data' => $chartData,
        ]);
    }

    public function adminActivities(Request $request)
    {
        if (Auth::user()->isTeacher()) {
            return $this->teacherItemClaims($request);
        }

        return $this->adminStudentActivities($request);
    }

    private function teacherItemClaims(Request $request)
    {
        $query = Student::whereNotIn('status', ['Archived', 'archived'])
            ->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lrn', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $students = $query
            ->with('enrollments')
            ->with('claims')
            ->withCount('claims as total_claims')
            ->withSum('claims as total_points_used', 'points_deducted')
            ->withMax('claims as latest_claim_date', 'claim_date')
            ->orderByDesc('total_claims')
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin-activities', compact('students'));
    }

    private function adminStudentActivities(Request $request)
    {
        $gradeLevels = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];

        $teacherFilter = $request->get('teacher_id');
        $gradeLevel = $request->get('grade_level');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $teachers = User::where('role', 'teacher')
            ->when($teacherFilter, function ($q) use ($teacherFilter) {
                $q->where('id', $teacherFilter);
            })
            ->orderBy('name')
            ->get();

        $teacherGroups = [];
        foreach ($teachers as $teacher) {
            $query = Student::whereNotIn('status', ['Archived', 'archived'])
                ->whereHas('enrollments', function ($q) use ($teacher) {
                    $q->where('teacher_id', $teacher->id)->where('status', 'active');
                })
                ->when($gradeLevel, function ($q) use ($gradeLevel) {
                    $q->where('grade_level', $gradeLevel);
                })
                ->with('enrollments')
                ->withSum(['bottleCollections as total_bottles' => function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, function ($qq) use ($dateFrom) {
                        $qq->whereDate('collection_date', '>=', $dateFrom);
                    })->when($dateTo, function ($qq) use ($dateTo) {
                        $qq->whereDate('collection_date', '<=', $dateTo);
                    });
                }], 'bottle_count')
                ->withCount(['claims as total_claims' => function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, function ($qq) use ($dateFrom) {
                        $qq->whereDate('claim_date', '>=', $dateFrom);
                    })->when($dateTo, function ($qq) use ($dateTo) {
                        $qq->whereDate('claim_date', '<=', $dateTo);
                    });
                }]);

            $allStudents = $query->get();
            $studentCount = $allStudents->count();
            $totalBottles = $allStudents->sum('total_bottles');
            $totalClaims = $allStudents->sum('total_claims');
            $totalPoints = $allStudents->sum('total_points');

            $studentClaimed = Student::whereNotIn('status', ['Archived', 'archived'])
                ->whereHas('enrollments', function ($q) use ($teacher) {
                    $q->where('teacher_id', $teacher->id)->where('status', 'active');
                })
                ->when($gradeLevel, function ($q) use ($gradeLevel) {
                    $q->where('grade_level', $gradeLevel);
                })
                ->whereHas('claims', function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, function ($qq) use ($dateFrom) {
                        $qq->whereDate('claim_date', '>=', $dateFrom);
                    })->when($dateTo, function ($qq) use ($dateTo) {
                        $qq->whereDate('claim_date', '<=', $dateTo);
                    });
                })
                ->count();

            $teacherGroups[] = [
                'teacher' => $teacher,
                'student_count' => $studentCount,
                'student_claimed' => $studentClaimed,
                'total_bottles' => $totalBottles,
                'total_points' => $totalPoints,
                'total_claims' => $totalClaims,
            ];
        }

        // Unassigned Students group — eligible students with no teacher assignment
        $unassignedStudents = Student::whereNotIn('status', ['Archived', 'archived'])
            ->whereDoesntHave('enrollments', function ($q) {
                $q->where('status', 'active');
            })
            ->whereNull('teacher_id')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $unassignedCount = $unassignedStudents->count();

        if ($unassignedCount > 0) {
            $teacherGroups[] = [
                'teacher' => null,
                'is_unassigned' => true,
                'student_count' => $unassignedCount,
                'student_claimed' => 0,
                'total_bottles' => 0,
                'total_points' => 0,
                'total_claims' => 0,
            ];
        }

        // Overall total — unique eligible students, matching Dashboard scope
        $totalStudents = Student::where('status', '!=', 'Archived')
            ->where(function ($q) {
                $q->whereHas('enrollments', function ($eq) {
                    $eq->where('status', 'active');
                })
                ->orWhereNotNull('teacher_id');
            })
            ->count();

        return view('pages.admin-activities', compact('teacherGroups', 'teachers', 'gradeLevels', 'dateFrom', 'dateTo', 'gradeLevel', 'teacherFilter', 'totalStudents'));
    }

    public function teacherStudents(Request $request)
    {
        $teacherId = $request->get('teacher_id');
        $search = $request->get('search');
        $sort = $request->get('sort', 'alpha');
        $page = $request->get('page', 1);
        $gradeLevel = $request->get('grade_level');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $teacher = User::where('role', 'teacher')->where('id', $teacherId)->firstOrFail();

        $query = Student::whereNotIn('status', ['Archived', 'archived'])
            ->whereHas('enrollments', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)->where('status', 'active');
            })
            ->when($gradeLevel, function ($q) use ($gradeLevel) {
                $q->where('grade_level', $gradeLevel);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('lrn', 'like', "%{$search}%")
                       ->orWhere('student_id', 'like', "%{$search}%")
->orWhere('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->with('enrollments')
            ->withSum(['bottleCollections as total_bottles' => function ($q) use ($dateFrom, $dateTo) {
                $q->when($dateFrom, function ($qq) use ($dateFrom) {
                    $qq->whereDate('collection_date', '>=', $dateFrom);
                })->when($dateTo, function ($qq) use ($dateTo) {
                    $qq->whereDate('collection_date', '<=', $dateTo);
                });
            }], 'bottle_count')
            ->withCount(['claims as total_claims' => function ($q) use ($dateFrom, $dateTo) {
                $q->when($dateFrom, function ($qq) use ($dateFrom) {
                    $qq->whereDate('claim_date', '>=', $dateFrom);
                })->when($dateTo, function ($qq) use ($dateTo) {
                    $qq->whereDate('claim_date', '<=', $dateTo);
                });
            }])
            ->withMax(['bottleCollections as latest_collection_date' => function ($q) use ($dateFrom, $dateTo) {
                $q->when($dateFrom, function ($qq) use ($dateFrom) {
                    $qq->whereDate('collection_date', '>=', $dateFrom);
                })->when($dateTo, function ($qq) use ($dateTo) {
                    $qq->whereDate('collection_date', '<=', $dateTo);
                });
            }], 'collection_date')
            ->withMax(['claims as latest_claim_date' => function ($q) use ($dateFrom, $dateTo) {
                $q->when($dateFrom, function ($qq) use ($dateFrom) {
                    $qq->whereDate('claim_date', '>=', $dateFrom);
                })->when($dateTo, function ($qq) use ($dateTo) {
                    $qq->whereDate('claim_date', '<=', $dateTo);
                });
            }], 'claim_date');

        switch ($sort) {
            case 'most':
                $query->orderByRaw('COALESCE(total_bottles, 0) DESC')->orderBy('first_name')->orderBy('last_name');
                break;
            case 'least':
                $query->orderByRaw('COALESCE(total_bottles, 0) ASC')->orderBy('first_name')->orderBy('last_name');
                break;
            case 'alpha':
            default:
                $query->orderBy('first_name')->orderBy('last_name');
                break;
        }

        $students = $query->paginate(10)->withQueryString();

        return view('partials.teacher-students-table', compact(
            'teacher',
            'students',
            'sort',
            'search',
            'gradeLevel',
            'dateFrom',
            'dateTo',
            'teacherId'
        ))->render();
    }

    public function studentClaims(Request $request)
    {
        $studentId = $request->get('student_id');

        $student = Student::findOrFail($studentId);

        $claims = StudentClaim::with(['item', 'admin', 'approver', 'releaser'])
            ->where('student_id', $studentId)
            ->orderByDesc('claim_date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'lrn' => $student->lrn,
            ],
            'claims' => $claims->map(function ($claim) {
                return [
                    'id' => $claim->id,
                    'item_name' => $claim->item_name,
                    'quantity' => $claim->quantity,
                    'points_deducted' => $claim->points_deducted ?? 0,
                    'claim_date' => $claim->claim_date ? \Carbon\Carbon::parse($claim->claim_date)->format('M d, Y') : 'N/A',
                    'status' => $claim->status ?? 'Pending',
                ];
            }),
        ]);
    }

    // ==================== PRINT METHODS ====================

    private function applyTeacherScope($query)
    {
        return $query->whereHas('enrollments', function ($q) {
            $q->where('teacher_id', Auth::id())->where('status', 'active');
        });
    }

    public function printTopStudents(Request $request)
    {
        $user = Auth::user();
        $isTeacher = $user->isTeacher();
        $quarter = (int) $request->get('quarter', date('n'));
        $year = (int) $request->get('year', now()->year);

        // Calculate quarter date range
        $quarterStart = \Carbon\Carbon::create($year, ($quarter - 1) * 3 + 1, 1)->startOfDay();
        $quarterEnd = \Carbon\Carbon::create($year, $quarter * 3, 1)->startOfDay()->addMonth(1)->subDay()->endOfDay();

        $studentsBase = Student::whereNotIn('status', ['Archived', 'archived']);

        if ($isTeacher) {
            $this->applyTeacherScope($studentsBase);
        }

        $topStudents = $studentsBase
            ->withSum(['bottleCollections as quarter_bottles' => function ($q) use ($quarterStart, $quarterEnd) {
                $q->whereBetween('collection_date', [$quarterStart, $quarterEnd]);
            }], 'bottle_count')
            ->having('quarter_bottles', '>', 0)
            ->orderByDesc('quarter_bottles')
            ->orderBy('full_name', 'asc')
            ->take($isTeacher ? 3 : 10)
            ->get();

        $quarterLabel = 'Q' . $quarter;
        $title = $isTeacher ? "Top 3 Students — $quarterLabel $year" : "Top 10 Students — $quarterLabel $year";

        return view('pages.reports.print.top-students', compact('topStudents', 'title', 'isTeacher', 'quarter', 'year'));
    }

    public function topStudentsData(Request $request)
    {
        $user = Auth::user();
        $isTeacher = $user->isTeacher();
        $quarter = (int) $request->get('quarter', date('n'));
        $year = (int) $request->get('year', now()->year);

        // Calculate quarter date range
        $quarterStart = \Carbon\Carbon::create($year, ($quarter - 1) * 3 + 1, 1)->startOfDay();
        $quarterEnd = \Carbon\Carbon::create($year, $quarter * 3, 1)->startOfDay()->addMonth(1)->subDay()->endOfDay();

        $studentsBase = Student::whereNotIn('status', ['Archived', 'archived']);

        if ($isTeacher) {
            $studentsBase->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', $user->id)->where('status', 'active');
            });
        }

        $topStudents = $studentsBase
            ->withSum(['bottleCollections as quarter_bottles' => function ($q) use ($quarterStart, $quarterEnd) {
                $q->whereBetween('collection_date', [$quarterStart, $quarterEnd]);
            }], 'bottle_count')
            ->having('quarter_bottles', '>', 0)
            ->orderByDesc('quarter_bottles')
            ->orderBy('full_name', 'asc')
            ->take($isTeacher ? 3 : 10)
            ->get();

        return response()->json([
            'success' => true,
            'quarter' => $quarter,
            'year' => $year,
            'students' => $topStudents->map(function ($s) {
                return [
                    'full_name' => $s->full_name,
                    'grade_level' => $s->grade_level,
                    'quarter_bottles' => (int) ($s->quarter_bottles ?? 0),
                ];
            }),
        ]);
    }

    public function printStudentRanking(Request $request)
    {
        if (Auth::user()->isTeacher()) {
            return $this->printTeacherStudentRanking($request);
        }

        $baseQuery = Student::whereNotIn('status', ['Archived', 'archived']);
        $baseQuery->visibleTo(Auth::user());

        if ($gradeLevel = $request->get('grade_level')) {
            $baseQuery->where('grade_level', $gradeLevel);
        }
        if ($gender = $request->get('gender')) {
            $baseQuery->where('gender', $gender);
        }
        if ($search = $request->get('search')) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('student_id', 'like', "%{$search}%")
                  ->orWhere('lrn', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('grade_level', 'like', "%{$search}%")
                  ->orWhere('gender', 'like', "%{$search}%");
            });
        }

        [$startDate, $endDate] = $this->getQuarterDateRange($request->get('quarter'));

        if ($startDate && $endDate) {
            $baseQuery->withSum(['bottleCollections as bottles_collected' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('collection_date', [$startDate, $endDate]);
            }], 'bottle_count');
        } else {
            $baseQuery->withSum('bottleCollections as bottles_collected', 'bottle_count');
        }

        $students = $baseQuery->orderBy('first_name')->orderBy('last_name')->get();

        return view('pages.reports.print.student-report', [
            'students' => $students,
            'title' => 'Student Report',
            'isTeacher' => false,
        ]);
    }

    private function printTeacherStudentRanking(Request $request)
    {
        $query = Student::whereNotIn('status', ['Archived', 'archived'])
            ->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lrn', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $students = $query
            ->with('enrollments')
            ->withSum('bottleCollections as total_bottles', 'bottle_count')
            ->withCount('claims as total_claims')
            ->withMax('bottleCollections as latest_collection_date', 'collection_date')
            ->orderByDesc('total_points')
            ->orderByDesc('total_bottles')
            ->get();

        return view('pages.reports.print.student-report', [
            'students' => $students,
            'title' => 'Student Ranking Report',
            'isTeacher' => true,
        ]);
    }

    public function printBottleCollection(Request $request)
    {
        $isTeacher = Auth::user()->isTeacher();

        $baseQuery = BottleCollection::with('student');

        if ($isTeacher) {
            $baseQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        if ($day = $request->get('day')) {
            $baseQuery->whereDay('collection_date', $day);
        }
        if ($month = $request->get('month')) {
            $monthNames = [
                'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4,
                'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8,
                'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12
            ];
            $monthNum = is_numeric($month) ? $month : ($monthNames[$month] ?? null);
            if ($monthNum) {
                $baseQuery->whereMonth('collection_date', $monthNum);
            }
        }
        if ($year = $request->get('year')) {
            $baseQuery->whereYear('collection_date', $year);
        }
        if ($quarter = $request->get('quarter')) {
            [$startDate, $endDate] = $this->getQuarterDateRange($quarter);
            if ($startDate && $endDate) {
                $baseQuery->whereBetween('collection_date', [$startDate, $endDate]);
            }
        }
        if ($search = $request->get('search')) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('lrn', 'like', "%{$search}%")
                  ->orWhere('bottle_count', 'like', "%{$search}%")
                  ->orWhereDate('collection_date', $search)
                  ->orWhereTime('collection_time', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($studentQuery) use ($search) {
                      $studentQuery->where('full_name', 'like', "%{$search}%")
                                   ->orWhere('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $collections = $baseQuery->orderBy('collection_date', 'desc')->get();

        return view('pages.reports.print.bottle-collection', [
            'collections' => $collections,
            'title' => 'Bottle Collection Report',
            'isTeacher' => $isTeacher,
        ]);
    }

    public function printItemClaims(Request $request)
    {
        $query = Student::whereNotIn('status', ['Archived', 'archived'])
            ->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lrn', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $students = $query
            ->with('enrollments')
            ->with('claims')
            ->withCount('claims as total_claims')
            ->withSum('claims as total_points_used', 'points_deducted')
            ->withMax('claims as latest_claim_date', 'claim_date')
            ->orderByDesc('total_claims')
            ->get();

        return view('pages.reports.print.item-claims', [
            'students' => $students,
            'title' => 'Item Claims Report',
        ]);
    }

    public function printStudentActivities(Request $request)
    {
        $gradeLevels = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];

        $teacherFilter = $request->get('teacher_id');
        $gradeLevel = $request->get('grade_level');
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $teachers = User::where('role', 'teacher')
            ->when($teacherFilter, function ($q) use ($teacherFilter) {
                $q->where('id', $teacherFilter);
            })
            ->orderBy('name')
            ->get();

        $teacherGroups = [];
        foreach ($teachers as $teacher) {
            $students = Student::whereNotIn('status', ['Archived', 'archived'])
                ->where('teacher_id', $teacher->id)
                ->when($gradeLevel, function ($q) use ($gradeLevel) {
                    $q->where('grade_level', $gradeLevel);
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($qq) use ($search) {
                        $qq->where('lrn', 'like', "%{$search}%")
                           ->orWhere('student_id', 'like', "%{$search}%")
                           ->orWhere('full_name', 'like', "%{$search}%")
                           ->orWhere('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%");
                    });
                })
                ->with('enrollments')
                ->withSum(['bottleCollections as total_bottles' => function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, function ($qq) use ($dateFrom) {
                        $qq->whereDate('collection_date', '>=', $dateFrom);
                    })->when($dateTo, function ($qq) use ($dateTo) {
                        $qq->whereDate('collection_date', '<=', $dateTo);
                    });
                }], 'bottle_count')
                ->withCount(['claims as total_claims' => function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, function ($qq) use ($dateFrom) {
                        $qq->whereDate('claim_date', '>=', $dateFrom);
                    })->when($dateTo, function ($qq) use ($dateTo) {
                        $qq->whereDate('claim_date', '<=', $dateTo);
                    });
                }])
                ->withMax(['bottleCollections as latest_collection_date' => function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, function ($qq) use ($dateFrom) {
                        $qq->whereDate('collection_date', '>=', $dateFrom);
                    })->when($dateTo, function ($qq) use ($dateTo) {
                        $qq->whereDate('collection_date', '<=', $dateTo);
                    });
                }], 'collection_date')
                ->withMax(['claims as latest_claim_date' => function ($q) use ($dateFrom, $dateTo) {
                    $q->when($dateFrom, function ($qq) use ($dateFrom) {
                        $qq->whereDate('claim_date', '>=', $dateFrom);
                    })->when($dateTo, function ($qq) use ($dateTo) {
                        $qq->whereDate('claim_date', '<=', $dateTo);
                    });
                }], 'claim_date')
                ->orderByDesc('total_points')
                ->orderByDesc('total_bottles')
                ->get();

            $teacherGroups[] = [
                'teacher' => $teacher,
                'students' => $students,
            ];
        }

        return view('pages.reports.print.student-activities', [
            'teacherGroups' => $teacherGroups,
            'title' => 'Student Activities Report',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'grade_level' => $gradeLevel,
        ]);
    }
}
