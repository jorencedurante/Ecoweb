<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\BottleCollection;
use App\Models\Teacher;
use App\Models\CertificateAward;
use App\Models\StudentClaim;
use App\Models\ClaimItem;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents = Student::where('status', '!=', 'Archived')->count();
        $totalBottles = BottleCollection::sum('bottle_count');
        $totalTeachers = Teacher::count();
        $totalAwards = CertificateAward::count();
        $totalClaimedItems = StudentClaim::count();
        $totalPointsRedeemed = StudentClaim::sum('points_deducted');
        $availableClaimItems = ClaimItem::where('status', 'Available')->count();
        $femaleCount = Student::where('gender', 'Female')->where('status', '!=', 'Archived')->count();
        $maleCount = Student::where('gender', 'Male')->where('status', '!=', 'Archived')->count();

        // Weekly collection data (last 7 days)
        $weeklyData = BottleCollection::selectRaw('DAYNAME(collection_date) as day, SUM(bottle_count) as total')
            ->where('collection_date', '>=', now()->subDays(6))
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
