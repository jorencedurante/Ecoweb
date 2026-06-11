<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BottleCollectionSeeder extends Seeder
{
    public function run(): void
    {
        $collections = [
            ['student_id' => 1, 'lrn' => '123456789012', 'collection_date' => '2025-01-06', 'collection_time' => '08:30:00', 'bottle_count' => 5, 'points_earned' => 5, 'recorded_by' => 1],
            ['student_id' => 2, 'lrn' => '123456789013', 'collection_date' => '2025-01-06', 'collection_time' => '09:00:00', 'bottle_count' => 3, 'points_earned' => 3, 'recorded_by' => 1],
            ['student_id' => 3, 'lrn' => '123456789014', 'collection_date' => '2025-01-06', 'collection_time' => '09:15:00', 'bottle_count' => 7, 'points_earned' => 7, 'recorded_by' => 1],
            ['student_id' => 4, 'lrn' => '123456789015', 'collection_date' => '2025-01-07', 'collection_time' => '08:45:00', 'bottle_count' => 4, 'points_earned' => 4, 'recorded_by' => 1],
            ['student_id' => 5, 'lrn' => '123456789016', 'collection_date' => '2025-01-07', 'collection_time' => '10:00:00', 'bottle_count' => 6, 'points_earned' => 6, 'recorded_by' => 1],
            ['student_id' => 6, 'lrn' => '123456789017', 'collection_date' => '2025-01-08', 'collection_time' => '08:20:00', 'bottle_count' => 8, 'points_earned' => 8, 'recorded_by' => 1],
            ['student_id' => 1, 'lrn' => '123456789012', 'collection_date' => '2025-01-08', 'collection_time' => '09:30:00', 'bottle_count' => 2, 'points_earned' => 2, 'recorded_by' => 1],
            ['student_id' => 2, 'lrn' => '123456789013', 'collection_date' => '2025-01-08', 'collection_time' => '10:15:00', 'bottle_count' => 5, 'points_earned' => 5, 'recorded_by' => 1],
            ['student_id' => 3, 'lrn' => '123456789014', 'collection_date' => '2025-01-09', 'collection_time' => '08:50:00', 'bottle_count' => 3, 'points_earned' => 3, 'recorded_by' => 1],
            ['student_id' => 4, 'lrn' => '123456789015', 'collection_date' => '2025-01-09', 'collection_time' => '09:10:00', 'bottle_count' => 6, 'points_earned' => 6, 'recorded_by' => 1],
        ];

        foreach ($collections as $c) {
            DB::table('bottle_collections')->insert($c + ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
