<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class LandingPageController extends Controller
{
    public function index()
    {
        $data = $this->getRankingData();
        return view('landing.index', $data);
    }

    public function searchByLrn(Request $request)
    {
        $request->validate([
            'lrn' => 'required|string|max:50',
        ]);

        $student = Student::with([
            'achievements',
            'certificateAwards',
            'bottleCollections' => function ($q) {
                $q->latest('collection_date')->take(5);
            },
        ])
        ->where('lrn', $request->lrn)
        ->where('status', '!=', 'Archived')
        ->first();

        $data = $this->getRankingData();
        $data['searchedStudent'] = $student;
        $data['searchedLrn'] = $request->lrn;

        return view('landing.index', $data);
    }

    private function getRankingData(): array
    {
        $currentQuarterStart = now()->firstOfQuarter()->startOfDay();
        $currentQuarterEnd = now()->lastOfQuarter()->endOfDay();

        $prevQuarterDate = now()->subQuarter();
        $prevQuarterStart = $prevQuarterDate->copy()->firstOfQuarter()->startOfDay();
        $prevQuarterEnd = $prevQuarterDate->copy()->lastOfQuarter()->endOfDay();

        $topOverall = Student::withSum('bottleCollections as total_bottles', 'bottle_count')
            ->withSum('bottleCollections as total_points', 'points_earned')
            ->where('status', '!=', 'Archived')
            ->orderByDesc('total_points')
            ->take(10)
            ->get();

        $topCurrentQuarter = Student::withSum(['bottleCollections as quarter_bottles' => function ($q) use ($currentQuarterStart, $currentQuarterEnd) {
                $q->whereBetween('collection_date', [$currentQuarterStart, $currentQuarterEnd]);
            }], 'bottle_count')
            ->withSum(['bottleCollections as quarter_points' => function ($q) use ($currentQuarterStart, $currentQuarterEnd) {
                $q->whereBetween('collection_date', [$currentQuarterStart, $currentQuarterEnd]);
            }], 'points_earned')
            ->where('status', '!=', 'Archived')
            ->orderByDesc('quarter_points')
            ->take(10)
            ->get();

        $topPrevQuarter = Student::withSum(['bottleCollections as prev_bottles' => function ($q) use ($prevQuarterStart, $prevQuarterEnd) {
                $q->whereBetween('collection_date', [$prevQuarterStart, $prevQuarterEnd]);
            }], 'bottle_count')
            ->withSum(['bottleCollections as prev_points' => function ($q) use ($prevQuarterStart, $prevQuarterEnd) {
                $q->whereBetween('collection_date', [$prevQuarterStart, $prevQuarterEnd]);
            }], 'points_earned')
            ->where('status', '!=', 'Archived')
            ->orderByDesc('prev_points')
            ->take(10)
            ->get();

        return compact('topOverall', 'topCurrentQuarter', 'topPrevQuarter');
    }
}
