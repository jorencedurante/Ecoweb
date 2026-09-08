<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AchievementController extends Controller
{
    /**
     * Only Admin and Super Admin can manage achievement quests.
     */
    private function authorizeQuestManagement(): void
    {
        if (!Auth::user()->isAdminLevel()) {
            abort(403, 'Only Admin and Super Admin can manage achievement quests.');
        }
    }

    private function validatedQuestData(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'requirement_type' => 'required|in:points,bottles',
            'required_value' => 'required|integer|min:1',
            'badge_name' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive',
        ]);

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'badge_name' => $validated['badge_name'] ?? null,
            'milestone' => $validated['requirement_type'] === 'bottles' ? 'Bottle Collection' : 'Points',
            'required_bottles' => $validated['requirement_type'] === 'bottles' ? $validated['required_value'] : 0,
            'points_required' => $validated['requirement_type'] === 'points' ? $validated['required_value'] : 0,
            'status' => $validated['status'],
        ];
    }

    public function store(Request $request)
    {
        $this->authorizeQuestManagement();

        $achievement = Achievement::create(
            $this->validatedQuestData($request) + ['created_by' => Auth::id()]
        );

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Added Achievement Quest',
            'description' => 'Added achievement quest: ' . $achievement->title,
            'module' => 'Achievements',
        ]);

        return redirect()->route('admin.certificate')
            ->with('success', 'Achievement quest added successfully.');
    }

    public function update(Request $request, Achievement $achievement)
    {
        $this->authorizeQuestManagement();

        $achievement->update($this->validatedQuestData($request));

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Updated Achievement Quest',
            'description' => 'Updated achievement quest: ' . $achievement->title,
            'module' => 'Achievements',
        ]);

        return redirect()->route('admin.certificate')
            ->with('success', 'Achievement quest updated successfully.');
    }

    public function destroy(Achievement $achievement)
    {
        $this->authorizeQuestManagement();

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Deleted Achievement Quest',
            'description' => 'Deleted achievement quest: ' . $achievement->title,
            'module' => 'Achievements',
        ]);

        $achievement->delete();

        return redirect()->route('admin.certificate')
            ->with('success', 'Achievement quest deleted successfully.');
    }
}
