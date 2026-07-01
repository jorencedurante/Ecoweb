<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

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

    public function studentLookup(Request $request)
    {
        $request->validate([
            'lrn' => 'required|string|max:50',
        ]);

        $student = Student::with([
            'earnedAchievements.quest',
            'certificateAwards',
            'bottleCollections',
        ])
        ->where('lrn', $request->lrn)
        ->where('status', '!=', 'Archived')
        ->first();

        if (!$student) {
            return back()->withErrors([
                'lrn' => 'No student record found for this LRN.',
            ])->withInput();
        }

        [$currentQuarterTopStudents, $previousQuarterTopStudents] = $this->getLandingRankings();

        return view('public.landing', compact(
            'student',
            'currentQuarterTopStudents',
            'previousQuarterTopStudents'
        ));
    }

    public function studentDetails($lrn)
    {
        $student = Student::where('lrn', $lrn)
            ->with([
                'earnedAchievements.quest',
                'certificateAwards',
                'bottleCollections',
            ])
            ->where('status', '!=', 'Archived')
            ->firstOrFail();

        return view('landing-student-details', compact('student'));
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
            ->withSum(['bottleCollections as quarter_bottles' => function ($query) use ($currentQuarterStart, $currentQuarterEnd) {
                $query->whereBetween('collection_date', [$currentQuarterStart, $currentQuarterEnd]);
            }], 'bottle_count')
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
            ->withSum(['bottleCollections as prev_bottles' => function ($query) use ($previousQuarterStart, $previousQuarterEnd) {
                $query->whereBetween('collection_date', [$previousQuarterStart, $previousQuarterEnd]);
            }], 'bottle_count')
            ->where('status', '!=', 'Archived')
            ->orderByDesc('prev_points')
            ->orderByDesc('prev_bottles')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(10)
            ->get();

        if ($currentQuarterTopStudents->isEmpty()) {
            $currentQuarterTopStudents = Student::query()
                ->withSum('bottleCollections as total_bottles_collected', 'bottle_count')
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
}
