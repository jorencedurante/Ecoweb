<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\BottleCollection;
use App\Models\StudentAchievement;
use Tests\TestCase;

class AchievementQuestManagementTest extends TestCase
{
    private function questPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Green Streak',
            'description' => 'Collect five bottles',
            'requirement_type' => 'bottles',
            'required_value' => 5,
            'badge_name' => 'Green',
            'status' => 'Active',
        ], $overrides);
    }

    public function test_teacher_can_create_quest_owned_by_themselves(): void
    {
        $teacher = $this->createTeacher();

        $response = $this->actingAs($teacher)->post(
            route('achievement-quests.store'),
            $this->questPayload()
        );

        $response->assertRedirect(route('admin.certificate'));
        $response->assertSessionHas('success');

        $quest = Achievement::where('title', 'Green Streak')->first();
        $this->assertNotNull($quest);
        $this->assertSame($teacher->id, (int) $quest->created_by);
        $this->assertNull($quest->student_id);
        $this->assertSame(5, (int) $quest->required_bottles);
        $this->assertSame(0, (int) $quest->points_required);
    }

    public function test_points_requirement_stores_points_required_not_bottles(): void
    {
        $teacher = $this->createTeacher();

        $this->actingAs($teacher)->post(
            route('achievement-quests.store'),
            $this->questPayload(['requirement_type' => 'points', 'required_value' => 20])
        );

        $quest = Achievement::where('title', 'Green Streak')->first();
        $this->assertNotNull($quest);
        $this->assertSame(0, (int) $quest->required_bottles);
        $this->assertSame(20, (int) $quest->points_required);
    }

    public function test_admin_cannot_create_update_or_delete_quests(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $quest = Achievement::create([
            'title' => 'Teacher Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 5,
            'points_required' => 0,
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($admin)
            ->post(route('achievement-quests.store'), $this->questPayload(['title' => 'Admin Quest']))
            ->assertForbidden();
        $this->assertDatabaseMissing('achievements', ['title' => 'Admin Quest']);

        $this->actingAs($admin)
            ->put(route('achievement-quests.update', $quest), $this->questPayload(['title' => 'Renamed']))
            ->assertForbidden();
        $this->assertSame('Teacher Quest', $quest->fresh()->title);

        $this->actingAs($admin)
            ->delete(route('achievement-quests.destroy', $quest))
            ->assertForbidden();
        $this->assertDatabaseHas('achievements', ['id' => $quest->id]);
    }

    public function test_guest_is_redirected_from_quest_endpoints(): void
    {
        $teacher = $this->createTeacher();
        $quest = Achievement::create([
            'title' => 'Teacher Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 5,
            'points_required' => 0,
            'created_by' => $teacher->id,
        ]);

        $this->post(route('achievement-quests.store'), $this->questPayload())->assertRedirect();
        $this->put(route('achievement-quests.update', $quest), $this->questPayload())->assertRedirect();
        $this->delete(route('achievement-quests.destroy', $quest))->assertRedirect();
    }

    public function test_quest_limit_is_per_teacher_not_global(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();

        for ($i = 1; $i <= 5; $i++) {
            Achievement::create([
                'title' => "A Quest {$i}",
                'student_id' => null,
                'status' => 'Active',
                'required_bottles' => $i,
                'points_required' => 0,
                'created_by' => $teacherA->id,
            ]);
        }

        $this->actingAs($teacherA)
            ->post(route('achievement-quests.store'), $this->questPayload(['title' => 'A Sixth Quest']))
            ->assertSessionHasErrors('title');
        $this->assertDatabaseMissing('achievements', ['title' => 'A Sixth Quest']);

        $this->actingAs($teacherB)
            ->post(route('achievement-quests.store'), $this->questPayload(['title' => 'B First Quest']))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('achievements', ['title' => 'B First Quest', 'created_by' => $teacherB->id]);
    }

    public function test_teacher_can_update_own_quest(): void
    {
        $teacher = $this->createTeacher();
        $quest = Achievement::create([
            'title' => 'Old Title',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 5,
            'points_required' => 0,
            'created_by' => $teacher->id,
        ]);

        $response = $this->actingAs($teacher)->put(
            route('achievement-quests.update', $quest),
            $this->questPayload(['title' => 'New Title', 'requirement_type' => 'points', 'required_value' => 30])
        );

        $response->assertRedirect(route('admin.certificate'));
        $quest->refresh();
        $this->assertSame('New Title', $quest->title);
        $this->assertSame(0, (int) $quest->required_bottles);
        $this->assertSame(30, (int) $quest->points_required);
    }

    public function test_teacher_cannot_update_or_delete_another_teachers_quest(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $questOfA = Achievement::create([
            'title' => 'Quest Of A',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 5,
            'points_required' => 0,
            'created_by' => $teacherA->id,
        ]);

        $this->actingAs($teacherB)
            ->put(route('achievement-quests.update', $questOfA), $this->questPayload(['title' => 'Hijacked']))
            ->assertForbidden();
        $this->assertSame('Quest Of A', $questOfA->fresh()->title);

        $this->actingAs($teacherB)
            ->delete(route('achievement-quests.destroy', $questOfA))
            ->assertForbidden();
        $this->assertDatabaseHas('achievements', ['id' => $questOfA->id]);
    }

    public function test_teacher_cannot_touch_legacy_unowned_quest(): void
    {
        $teacher = $this->createTeacher();
        $legacy = Achievement::create([
            'title' => 'Legacy Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 5,
            'points_required' => 0,
            'created_by' => null,
        ]);

        $this->actingAs($teacher)
            ->put(route('achievement-quests.update', $legacy), $this->questPayload(['title' => 'Nope']))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->delete(route('achievement-quests.destroy', $legacy))
            ->assertForbidden();
        $this->assertSame('Legacy Quest', $legacy->fresh()->title);
    }

    public function test_certificate_page_scopes_quest_list_and_controls_by_role(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $admin = $this->createAdmin();

        Achievement::create([
            'title' => 'Quest Of A',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 5,
            'points_required' => 0,
            'created_by' => $teacherA->id,
        ]);
        Achievement::create([
            'title' => 'Quest Of B',
            'student_id' => null,
            'status' => 'Active',
            'points_required' => 10,
            'required_bottles' => 0,
            'created_by' => $teacherB->id,
        ]);

        $asTeacherA = $this->actingAs($teacherA)->get(route('admin.certificate'));
        $asTeacherA->assertOk();
        $asTeacherA->assertSee('Quest Of A', false);
        $asTeacherA->assertDontSee('Quest Of B', false);
        $asTeacherA->assertSee('1 of 5 quests', false);
        $asTeacherA->assertSee('Add Achievement Quest', false);
        $asTeacherA->assertSee('questAddModal', false);
        $asTeacherA->assertSee('questEditModal', false);
        $asTeacherA->assertSee('openQuestEditModal(', false);

        $asAdmin = $this->actingAs($admin)->get(route('admin.certificate'));
        $asAdmin->assertOk();
        $asAdmin->assertSee('Quest Of A', false);
        $asAdmin->assertSee('Quest Of B', false);
        $asAdmin->assertSee('Monitoring', false);
        $asAdmin->assertDontSee('Add Achievement Quest', false);
        $asAdmin->assertDontSee('questAddModal', false);
        $asAdmin->assertDontSee('questEditModal', false);
        $asAdmin->assertDontSee('openQuestEditModal(', false);
        $asAdmin->assertDontSee('openAddQuestModal(', false);
    }

    public function test_teacher_at_limit_sees_disabled_maximum_message_without_add_button(): void
    {
        $teacher = $this->createTeacher();

        for ($i = 1; $i <= 5; $i++) {
            Achievement::create([
                'title' => "Owned {$i}",
                'student_id' => null,
                'status' => 'Active',
                'required_bottles' => $i,
                'points_required' => 0,
                'created_by' => $teacher->id,
            ]);
        }

        $response = $this->actingAs($teacher)->get(route('admin.certificate'));
        $response->assertOk();
        $response->assertSee('Maximum of 5 achievement quests reached', false);
        $response->assertDontSee('Add Achievement Quest', false);
        $response->assertSee('questEditModal', false);
    }

    public function test_bottle_collection_awards_only_assigned_teachers_quest(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $student = $this->createStudent($teacherA);

        $questA = Achievement::create([
            'title' => 'A Bottle Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 3,
            'points_required' => 0,
            'created_by' => $teacherA->id,
        ]);
        $questB = Achievement::create([
            'title' => 'B Bottle Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 3,
            'points_required' => 0,
            'created_by' => $teacherB->id,
        ]);

        $this->actingAs($teacherA)->post(route('admin.bottle-collection.store'), [
            'student_id' => $student->id,
            'collection_date' => now()->toDateString(),
            'collection_time' => '08:30',
            'bottle_count' => 3,
        ])->assertRedirect(route('admin.bottle-collection'));

        $this->assertDatabaseHas('student_achievements', [
            'student_id' => $student->id,
            'achievement_quest_id' => $questA->id,
        ]);
        $this->assertDatabaseMissing('student_achievements', [
            'student_id' => $student->id,
            'achievement_quest_id' => $questB->id,
        ]);
    }

    public function test_points_quest_completes_from_total_points_not_bottle_count(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, ['total_points' => 10]);

        $pointsQuest = Achievement::create([
            'title' => 'Points Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 0,
            'points_required' => 10,
            'created_by' => $teacher->id,
        ]);
        $bottleQuest = Achievement::create([
            'title' => 'Bottle Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 10,
            'points_required' => 0,
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($teacher)->post(route('admin.bottle-collection.store'), [
            'student_id' => $student->id,
            'collection_date' => now()->toDateString(),
            'collection_time' => '08:30',
            'bottle_count' => 1,
        ])->assertRedirect(route('admin.bottle-collection'));

        $this->assertDatabaseHas('student_achievements', [
            'student_id' => $student->id,
            'achievement_quest_id' => $pointsQuest->id,
        ]);
        $this->assertDatabaseMissing('student_achievements', [
            'student_id' => $student->id,
            'achievement_quest_id' => $bottleQuest->id,
        ]);
    }

    public function test_unassigned_student_earns_no_quest(): void
    {
        $teacher = $this->createTeacher();
        $admin = $this->createAdmin();
        $student = $this->createStudent();

        $quest = Achievement::create([
            'title' => 'Unreachable Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 1,
            'points_required' => 0,
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($admin)->post(route('admin.bottle-collection.store'), [
            'student_id' => $student->id,
            'collection_date' => now()->toDateString(),
            'collection_time' => '08:30',
            'bottle_count' => 5,
        ])->assertRedirect(route('admin.bottle-collection'));

        $this->assertDatabaseMissing('student_achievements', [
            'student_id' => $student->id,
            'achievement_quest_id' => $quest->id,
        ]);
    }

    public function test_public_landing_syncs_only_assigned_teachers_quests(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $student = $this->createStudent($teacherA);

        BottleCollection::create([
            'student_id' => $student->id,
            'lrn' => $student->lrn,
            'collection_date' => now()->toDateString(),
            'collection_time' => '08:30',
            'bottle_count' => 3,
            'points_earned' => 3,
            'created_by' => $teacherA->id,
        ]);

        $questA = Achievement::create([
            'title' => 'A Landing Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 3,
            'points_required' => 0,
            'created_by' => $teacherA->id,
        ]);
        $questB = Achievement::create([
            'title' => 'B Landing Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 3,
            'points_required' => 0,
            'created_by' => $teacherB->id,
        ]);

        $this->get(route('landing.student.details', $student->lrn))->assertOk();

        $this->assertDatabaseHas('student_achievements', [
            'student_id' => $student->id,
            'achievement_quest_id' => $questA->id,
        ]);
        $this->assertDatabaseMissing('student_achievements', [
            'student_id' => $student->id,
            'achievement_quest_id' => $questB->id,
        ]);
    }

    public function test_student_achievements_page_scopes_active_quests_but_keeps_earned_history(): void
    {
        $teacher = $this->createTeacher();
        $otherTeacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        $assignedQuest = Achievement::create([
            'title' => 'Assigned Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 5,
            'points_required' => 0,
            'created_by' => $teacher->id,
        ]);
        Achievement::create([
            'title' => 'Other Teachers Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 5,
            'points_required' => 0,
            'created_by' => $otherTeacher->id,
        ]);
        $legacyQuest = Achievement::create([
            'title' => 'Legacy Earned Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 5,
            'points_required' => 0,
            'created_by' => null,
        ]);
        StudentAchievement::create([
            'student_id' => $student->id,
            'achievement_quest_id' => $legacyQuest->id,
            'awarded_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($teacher)->get(route('students.achievements', $student->id));

        $response->assertOk();
        $response->assertSee('Assigned Quest', false);
        $response->assertDontSee('Other Teachers Quest', false);
        // Previously earned achievements remain visible even when their quest
        // is no longer part of the assigned teacher's active quest list.
        $response->assertSee('Legacy Earned Quest', false);
        $this->assertSame($assignedQuest->id, $assignedQuest->fresh()->id);
    }

    public function test_achievement_progress_uses_actual_bottle_sum_not_record_count(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, ['total_points' => 1]);

        BottleCollection::create([
            'student_id' => $student->id,
            'lrn' => $student->lrn,
            'collection_date' => now()->toDateString(),
            'collection_time' => '08:30',
            'bottle_count' => 5,
            'points_earned' => 1,
            'created_by' => $teacher->id,
        ]);
        Achievement::create([
            'title' => 'Ten Bottle Quest',
            'student_id' => null,
            'status' => 'Active',
            'required_bottles' => 10,
            'points_required' => 0,
            'created_by' => $teacher->id,
        ]);

        $data = $student->achievementProgressData();

        $this->assertSame(5, $data['totalBottles']);
        $this->assertSame(10, $data['nextGoalValue']);
        $this->assertSame(50, (int) round($data['progress']));
    }

    public function test_inactive_own_quest_does_not_evaluate_but_counts_toward_limit(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher);

        Achievement::create([
            'title' => 'Paused Quest',
            'student_id' => null,
            'status' => 'Inactive',
            'required_bottles' => 1,
            'points_required' => 0,
            'created_by' => $teacher->id,
        ]);

        $this->syncOrFail($student);

        $this->assertSame(0, StudentAchievement::where('student_id', $student->id)->count());

        $ownedCount = Achievement::whereNull('student_id')->where('created_by', $teacher->id)->count();
        $this->assertSame(1, $ownedCount);
    }

    private function syncOrFail($student): void
    {
        BottleCollection::create([
            'student_id' => $student->id,
            'lrn' => $student->lrn,
            'collection_date' => now()->toDateString(),
            'collection_time' => '09:00',
            'bottle_count' => 5,
            'points_earned' => 5,
            'created_by' => $student->teacher_id,
        ]);

        $student->syncEarnedAchievements();
    }
}
