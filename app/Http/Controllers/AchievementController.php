<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AchievementController extends Controller
{
    public function index()
    {
        $quests = Achievement::whereNull('student_id')->latest()->paginate(15);
        return view('achievements.index', compact('quests'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'badge_name' => 'nullable|string|max:150',
            'milestone' => 'nullable|string|max:150',
            'required_bottles' => 'required|integer|min:0',
            'points_required' => 'required|integer|min:0',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['created_by'] = Auth::id();

        $achievement = Achievement::create($validated);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Added Achievement Quest',
            'description' => 'Added achievement quest: ' . $achievement->title,
            'module' => 'Achievements',
        ]);

        return redirect()->back()->with('success', 'Achievement quest added successfully.');
    }

    public function update(Request $request, Achievement $achievement)
    {
        $validated = $request->validate([
            'required_bottles' => 'required|integer|min:0',
            'points_required' => 'required|integer|min:0',
        ]);

        $achievement->update($validated);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Updated Achievement Quest',
            'description' => 'Updated achievement quest: ' . $achievement->title,
            'module' => 'Achievements',
        ]);

        return redirect()->back()->with('success', 'Achievement quest updated successfully.');
    }
}
