<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\BottleCollection;
use App\Models\AdminActivity;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $totalStudents = Student::where('status', '!=', 'Archived')->count();
        $totalBottles = BottleCollection::sum('bottle_count');
        $totalAwards = \App\Models\CertificateAward::count();
        $femaleCount = Student::where('gender', 'Female')->where('status', '!=', 'Archived')->count();
        $maleCount = Student::where('gender', 'Male')->where('status', '!=', 'Archived')->count();

        $topStudents = Student::where('status', '!=', 'Archived')
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
        $query = Student::where('status', '!=', 'Archived');

        if ($gradeLevel = $request->get('grade_level')) {
            $query->where('grade_level', $gradeLevel);
        }

        if ($gender = $request->get('gender')) {
            $query->where('gender', $gender);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('lrn', 'like', "%{$search}%");
            });
        }

        $students = $query->withSum('bottleCollections', 'bottle_count')
            ->orderBy('last_name')
            ->paginate(10)
            ->withQueryString();

        $totalStudents = Student::where('status', '!=', 'Archived')->count();
        $femaleCount = Student::where('gender', 'Female')->where('status', '!=', 'Archived')->count();
        $maleCount = Student::where('gender', 'Male')->where('status', '!=', 'Archived')->count();
        $gradeLevels = Student::select('grade_level')->distinct()->pluck('grade_level');

        return view('pages.student-report', compact(
            'students', 'totalStudents', 'femaleCount', 'maleCount', 'gradeLevels'
        ));
    }

    public function bottleReport(Request $request)
    {
        $query = BottleCollection::with('student');

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

        if ($search = $request->get('search')) {
            $query->where('lrn', 'like', "%{$search}%");
        }

        $collections = $query->orderBy('collection_date', 'desc')->paginate(10)->withQueryString();

        $dailyTotal = BottleCollection::whereDate('collection_date', today())->sum('bottle_count');
        $weeklyTotal = BottleCollection::whereBetween('collection_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('bottle_count');
        $monthlyTotal = BottleCollection::whereMonth('collection_date', now()->month)
            ->whereYear('collection_date', now()->year)
            ->sum('bottle_count');

        return view('pages.bottle-report', compact(
            'collections', 'dailyTotal', 'weeklyTotal', 'monthlyTotal'
        ));
    }

    public function adminActivities()
    {
        $activities = AdminActivity::with('user')->latest()->paginate(15);
        return view('pages.admin-activities', compact('activities'));
    }
}
