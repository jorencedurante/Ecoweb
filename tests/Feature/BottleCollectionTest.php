<?php

namespace Tests\Feature;

use App\Models\BottleCollection;
use Tests\TestCase;

class BottleCollectionTest extends TestCase
{
    public function test_teacher_records_bottles_for_own_student(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent($teacher, ['total_points' => 0]);

        $response = $this->actingAs($teacher)->post(route('admin.bottle-collection'), [
            'student_id' => $student->id,
            'collection_date' => now()->toDateString(),
            'collection_time' => '08:30',
            'bottle_count' => 5,
        ]);

        $response->assertRedirect(route('admin.bottle-collection'));

        $this->assertDatabaseHas('bottle_collections', [
            'student_id' => $student->id,
            'bottle_count' => 5,
            'points_earned' => 5,
            'created_by' => $teacher->id,
        ]);

        $this->assertSame(5, (int) $student->refresh()->total_points);
    }

    public function test_teacher_cannot_record_bottles_for_another_teachers_student(): void
    {
        $teacherA = $this->createTeacher();
        $teacherB = $this->createTeacher();
        $studentOfA = $this->createStudent($teacherA);

        $response = $this->actingAs($teacherB)->post(route('admin.bottle-collection'), [
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

        $response = $this->actingAs($teacherB)->get(route('admin.bottle-collection'));

        $response->assertOk();
        $response->assertSee('BetaKid');
        $response->assertDontSee('AlphaKid');
    }
}
