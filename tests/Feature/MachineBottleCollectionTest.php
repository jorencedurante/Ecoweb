<?php

namespace Tests\Feature;

use App\Models\BottleCollection;
use Tests\TestCase;

class MachineBottleCollectionTest extends TestCase
{
    public function test_machine_bottle_scan_still_records_collection_and_points(): void
    {
        $student = $this->createStudent(null, ['total_points' => 0]);

        $response = $this->postJson('/api/bottle-scan', [
            'lrn' => $student->lrn,
            'bottle_count' => 3,
            'points' => 45,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('bottle_collections', [
            'student_id' => $student->id,
            'bottle_count' => 3,
            'points_earned' => 45,
        ]);
        $this->assertSame(45, (int) $student->refresh()->total_points);
    }

    public function test_machine_bottle_scan_rejects_invalid_submissions(): void
    {
        $this->postJson('/api/bottle-scan', [
            'lrn' => '', 'bottle_count' => 0, 'points' => 0,
        ])->assertStatus(422);

        $this->assertSame(0, BottleCollection::count());
    }
}
