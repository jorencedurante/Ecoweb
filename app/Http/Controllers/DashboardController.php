<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\BottleCollection;
use App\Models\Teacher;
use App\Models\User;
use App\Models\CertificateAward;
use App\Models\StudentClaim;
use App\Models\ClaimItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $studentsQuery = Student::where('status', '!=', 'Archived');
        $bottlesQuery = BottleCollection::query();
        $awardsQuery = CertificateAward::query();
        $claimsQuery = StudentClaim::query();

        if (Auth::user()->isTeacher()) {
            $studentsQuery->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
            $bottlesQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
            $awardsQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
            $claimsQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        $totalStudents = (clone $studentsQuery)->count();
        $totalBottles = (clone $bottlesQuery)->sum('bottle_count');
        $totalTeachers = Auth::user()->isAdminLevel()
            ? User::where('role', 'teacher')->where('status', 'active')->count()
            : 0;
        $totalAwards = (clone $awardsQuery)->count();
        $totalClaimedItems = (clone $claimsQuery)->count();
        $totalPointsRedeemed = (clone $claimsQuery)->sum('points_deducted');
        $availableClaimItems = ClaimItem::where('status', 'Available')->count();
        $femaleCount = (clone $studentsQuery)->where('gender', 'Female')->count();
        $maleCount = (clone $studentsQuery)->where('gender', 'Male')->count();

        // Weekly collection data (last 7 days)
        $weeklyBaseQuery = BottleCollection::selectRaw('DAYNAME(collection_date) as day, SUM(bottle_count) as total')
            ->where('collection_date', '>=', now()->subDays(6));
        if (Auth::user()->isTeacher()) {
            $weeklyBaseQuery->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }
        $weeklyData = $weeklyBaseQuery
            ->groupBy('day', 'collection_date')
            ->orderBy('collection_date')
            ->get()
            ->keyBy('day');

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $collectionData = [];
        foreach ($days as $day) {
            $collectionData[$day] = $weeklyData[$day]->total ?? 0;
        }

        return view('pages.dashboard', compact(
            'totalStudents', 'totalBottles', 'totalTeachers', 'totalAwards',
            'totalClaimedItems', 'totalPointsRedeemed', 'availableClaimItems',
            'femaleCount', 'maleCount', 'collectionData'
        ));
    }
}
