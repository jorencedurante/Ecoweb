<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AchievementController extends Controller
{
    public const MAX_QUESTS = 5;

    /**
     * Only teachers can manage achievement quests. Admin and Super Admin
     * retain read-only monitoring access on the Certificate page.
     */
    private function authorizeQuestManagement(): void
    {
        if (!Auth::user()->isTeacher()) {
            abort(403, 'Only teachers can manage achievement quests.');
        }
    }

    /**
     * Teachers may only edit or delete quests they own (created_by).
     */
    private function authorizeQuestOwnership(Achievement $achievement): void
    {
        if ((int) $achievement->created_by !== (int) Auth::id()) {
            abort(403, 'You can only modify achievement quests you created.');
        }
    }

    /**
     * The MAX_QUESTS limit applies per teacher, counting that teacher's
     * own quests (active or inactive) — not a global total.
     */
    private function questLimitReached(): bool
    {
        return Achievement::whereNull('student_id')
            ->where('created_by', Auth::id())
            ->count() >= self::MAX_QUESTS;
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

        if ($this->questLimitReached()) {
            return redirect()->route('admin.certificate')
                ->withInput()
                ->withErrors(['title' => 'Maximum of ' . self::MAX_QUESTS . ' achievement quests reached. Delete an existing quest before adding a new one.']);
        }

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
        $this->authorizeQuestOwnership($achievement);

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
        $this->authorizeQuestOwnership($achievement);

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
