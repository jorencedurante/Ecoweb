<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\BottleCollection;
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
            ->take(Auth::user()->isTeacher() ? 3 : 10)
            ->get();

        return view('pages.reports', compact(
            'totalStudents', 'totalBottles', 'totalAwards',
            'femaleCount', 'maleCount', 'topStudents'
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
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->when($teacherFilter, function ($q) use ($teacherFilter) {
                $q->where('id', $teacherFilter);
            })
            ->orderBy('name')
            ->get();

        $teacherGroups = [];
        foreach ($teachers as $teacher) {
            $students = Student::whereNotIn('status', ['Archived', 'archived'])
                ->whereTeacher($teacher->id)
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

        return view('pages.admin-activities', compact('teacherGroups', 'teachers', 'gradeLevels'));
    }

    // ==================== PRINT METHODS ====================

    private function applyTeacherScope($query)
    {
        return $query->whereHas('enrollments', function ($q) {
            $q->where('teacher_id', Auth::id())->where('status', 'active');
        });
    }

    public function printTopStudents()
    {
        $isTeacher = Auth::user()->isTeacher();

        $query = Student::whereNotIn('status', ['Archived', 'archived']);

        if ($isTeacher) {
            $this->applyTeacherScope($query);
        }

        $topStudents = $query
            ->orderBy('total_points', 'desc')
            ->take($isTeacher ? 3 : 10)
            ->get();

        $title = $isTeacher ? 'Top 3 Students Report' : 'Top 10 Overall Students Report';

        return view('pages.reports.print.top-students', compact('topStudents', 'title', 'isTeacher'));
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

        $students = $baseQuery->orderBy('full_name')->get();

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
            ->where('status', 'active')
            ->when($teacherFilter, function ($q) use ($teacherFilter) {
                $q->where('id', $teacherFilter);
            })
            ->orderBy('name')
            ->get();

        $teacherGroups = [];
        foreach ($teachers as $teacher) {
            $students = Student::whereNotIn('status', ['Archived', 'archived'])
                ->whereTeacher($teacher->id)
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
        ]);
    }
}
