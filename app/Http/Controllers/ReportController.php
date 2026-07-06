<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\BottleCollection;
use App\Models\AdminActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        $studentsBase = Student::whereNotIn('status', ['Archived', 'archived']);
        $bottlesBase = BottleCollection::query();
        $awardsBase = \App\Models\CertificateAward::query();

        if (Auth::user()->isTeacher()) {
            $studentsBase->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
            $bottlesBase->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
            $awardsBase->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        $totalStudents = (clone $studentsBase)->count();
        $totalBottles = (clone $bottlesBase)->sum('bottle_count');
        $totalAwards = (clone $awardsBase)->count();
        $femaleCount = (clone $studentsBase)->where('gender', 'Female')->count();
        $maleCount = (clone $studentsBase)->where('gender', 'Male')->count();

        $topStudents = (clone $studentsBase)
            ->orderBy('total_points', 'desc')
            ->take(5)
            ->get();

        return view('pages.reports', compact(
            'totalStudents', 'totalBottles', 'totalAwards',
            'femaleCount', 'maleCount', 'topStudents'
        ));
    }

    public function studentReport(Request $request)
    {
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

        [$startDate, $endDate] = $this->getQuarterDateRange($request->get('quarter'));

        if ($startDate && $endDate) {
            $baseQuery->withSum(['bottleCollections as bottles_collected' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('collection_date', [$startDate, $endDate]);
            }], 'bottle_count');
        } else {
            $baseQuery->withSum('bottleCollections as bottles_collected', 'bottle_count');
        }

        $students = $baseQuery->orderBy('full_name')->paginate(10)->withQueryString();
        $gradeLevels = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];

        return view('pages.student-report', compact(
            'students', 'totalStudents', 'femaleCount', 'maleCount', 'gradeLevels'
        ));
    }

    public function bottleReport(Request $request)
    {
        $baseQuery = BottleCollection::with('student');

        if (Auth::user()->isTeacher()) {
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

        $collections = $baseQuery->orderBy('collection_date', 'desc')->paginate(10)->withQueryString();

        $summaryQuery = BottleCollection::query();

        if (Auth::user()->isTeacher()) {
            $summaryQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        if ($day = $request->get('day')) {
            $summaryQuery->whereDay('collection_date', $day);
        }
        if ($month = $request->get('month')) {
            $monthNames = [
                'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4,
                'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8,
                'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12
            ];
            $monthNum = is_numeric($month) ? $month : ($monthNames[$month] ?? null);
            if ($monthNum) {
                $summaryQuery->whereMonth('collection_date', $monthNum);
            }
        }
        if ($year = $request->get('year')) {
            $summaryQuery->whereYear('collection_date', $year);
        }
        if ($quarter = $request->get('quarter')) {
            [$s, $e] = $this->getQuarterDateRange($quarter);
            if ($s && $e) {
                $summaryQuery->whereBetween('collection_date', [$s, $e]);
            }
        }
        if ($search = $request->get('search')) {
            $s = $search;
            $summaryQuery->where(function ($q) use ($s) {
                $q->where('lrn', 'like', "%{$s}%")
                  ->orWhere('bottle_count', 'like', "%{$s}%")
                  ->orWhereDate('collection_date', $s)
                  ->orWhereTime('collection_time', 'like', "%{$s}%")
                  ->orWhereHas('student', function ($sq) use ($s) {
                      $sq->where('full_name', 'like', "%{$s}%")
                         ->orWhere('first_name', 'like', "%{$s}%")
                         ->orWhere('last_name', 'like', "%{$s}%");
                  });
            });
        }

        $dailyTotal = (clone $summaryQuery)->whereDate('collection_date', today())->sum('bottle_count');
        $weeklyTotal = (clone $summaryQuery)->whereBetween('collection_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('bottle_count');
        $monthlyTotal = (clone $summaryQuery)->whereMonth('collection_date', now()->month)
            ->whereYear('collection_date', now()->year)
            ->sum('bottle_count');
        $totalBottles = (clone $summaryQuery)->sum('bottle_count');

        $trendQuery = BottleCollection::selectRaw('DAYNAME(collection_date) as day, SUM(bottle_count) as total')
            ->where('collection_date', '>=', now()->subDays(6));
        if (Auth::user()->isTeacher()) {
            $trendQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
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
            'collections', 'dailyTotal', 'weeklyTotal', 'monthlyTotal', 'totalBottles', 'chartData'
        ));
    }

    public function adminActivities(Request $request)
    {
        $query = AdminActivity::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->latest()->paginate(15)->withQueryString();
        $users = User::whereIn('role', ['admin', 'teacher', 'super_admin'])->orderBy('name')->get();
        $actions = AdminActivity::select('action')->distinct()->pluck('action');

        return view('pages.admin-activities', compact('activities', 'users', 'actions'));
    }
}
