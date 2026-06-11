<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

class PublicController extends Controller
{
    public function landing()
    {
        $data = $this->getRankingData();
        return view('public.landing', $data);
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

        $data = $this->getRankingData();
        $data['student'] = $student;

        return view('public.landing', $data);
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

    private function getRankingData(): array
    {
        // Use hardcoded placeholder data; replace with DB queries when live data is populated.
        $currentQuarterRankings = [];
        $previousQuarterRankings = [];
        $monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

        $fakes = [
            ['name' => 'Juan Dela Cruz',         'points' => 1250, 'bottles' => 86],
            ['name' => 'Maria Santos',           'points' => 1120, 'bottles' => 79],
            ['name' => 'Jose Rizal',             'points' => 1080, 'bottles' => 74],
            ['name' => 'Ana Gonzales',           'points' => 975,  'bottles' => 68],
            ['name' => 'Pedro Reyes',            'points' => 910,  'bottles' => 63],
            ['name' => 'Sofia Lopez',            'points' => 865,  'bottles' => 60],
            ['name' => 'Miguel Fernandez',       'points' => 790,  'bottles' => 55],
            ['name' => 'Isabella Torres',        'points' => 740,  'bottles' => 51],
            ['name' => 'Luis Garcia',            'points' => 680,  'bottles' => 47],
            ['name' => 'Carmen Villanueva',      'points' => 620,  'bottles' => 43],
        ];

        shuffle($fakes);
        foreach ($fakes as $i => $f) {
            $currentQuarterRankings[] = ['rank' => $i + 1] + $f;
        }

        $fakesPrev = [
            ['name' => 'Diego Ramos',            'points' => 980,  'bottles' => 70],
            ['name' => 'Elena Cruz',             'points' => 940,  'bottles' => 66],
            ['name' => 'Rafael Mendoza',         'points' => 890,  'bottles' => 62],
            ['name' => 'Lara Dizon',             'points' => 850,  'bottles' => 59],
            ['name' => 'Carlos Bautista',        'points' => 800,  'bottles' => 56],
            ['name' => 'Mia Villanueva',         'points' => 750,  'bottles' => 52],
            ['name' => 'Andres Castillo',        'points' => 710,  'bottles' => 49],
            ['name' => 'Tessa Manaloto',         'points' => 660,  'bottles' => 46],
            ['name' => 'Gabriel Navarro',        'points' => 610,  'bottles' => 42],
            ['name' => 'Paula Gomez',            'points' => 570,  'bottles' => 39],
        ];

        shuffle($fakesPrev);
        foreach ($fakesPrev as $i => $f) {
            $previousQuarterRankings[] = ['rank' => $i + 1] + $f;
        }

        return compact('currentQuarterRankings', 'previousQuarterRankings');
    }
}
