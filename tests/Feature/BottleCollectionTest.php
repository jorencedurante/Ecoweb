<?php

namespace Tests\Feature;

use App\Models\BottleCollection;
use Tests\TestCase;

class BottleCollectionTest extends TestCase
{
    public function test_manual_collection_creation_is_blocked(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, ['total_points' => 0]);

        $response = $this->actingAs($teacher)->post(route(portal_prefix() . '.bottle-collection'), [
            'student_id' => $student->id,
            'collection_date' => now()->toDateString(),
            'collection_time' => '08:30',
            'bottle_count' => 5,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('bottle_collections', ['student_id' => $student->id]);
        $this->assertSame(0, (int) $student->refresh()->total_points);
    }

    public function test_add_collection_ui_is_removed_for_teacher_admin_and_super_admin(): void
    {
        $teacher = $this->createTeacher();
        $admin = $this->createAdmin();
        $principal = $this->createSuperAdmin();

        foreach ([$teacher, $admin, $principal] as $user) {
            $prefix = $user->isTeacher() ? 'teacher' : 'admin';
            $html = (string) $this->actingAs($user)->get(route($prefix . '.bottle-collection'))->assertOk()->getContent();
            $this->assertStringNotContainsString('Add Collection', $html);
            $this->assertStringNotContainsString('addCollectionModal', $html);
            $this->assertStringNotContainsString('Save Collection', $html);
        }
    }

    public function test_teacher_cannot_record_bottles_for_another_teachers_student(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $studentOfA = $this->createStudent($teacherA);

        $response = $this->actingAs($teacherB)->post(route(portal_prefix() . '.bottle-collection'), [
            'student_id' => $studentOfA->id,
            'collection_date' => now()->toDateString(),
            'collection_time' => '08:30',
            'bottle_count' => 3,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('bottle_collections', ['student_id' => $studentOfA->id]);
    }

    public function test_teacher_bottle_collection_list_is_scoped_to_own_students(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();

        $studentA = $this->createStudent($teacherA, ['first_name' => 'AlphaKid', 'last_name' => 'One']);
        $studentB = $this->createStudent($teacherB, ['first_name' => 'BetaKid', 'last_name' => 'Two']);

        foreach ([[$studentA, 3], [$studentB, 7]] as [$student, $count]) {
            BottleCollection::create([
                'student_id' => $student->id,
                'lrn' => $student->lrn,
                'collection_date' => now()->toDateString(),
                'collection_time' => '09:00',
                'bottle_count' => $count,
                'points_earned' => $count,
                'created_by' => $teacherA->id,
            ]);
        }

        $response = $this->actingAs($teacherB)->get(route(portal_prefix() . '.bottle-collection'));

        $response->assertOk();
        $response->assertSee('BetaKid');
        $response->assertDontSee('AlphaKid');
    }
}
