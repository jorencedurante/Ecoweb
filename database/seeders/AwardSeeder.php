<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AwardSeeder extends Seeder
{
    public function run(): void
    {
        $awards = [
            [
                'student_id' => 1, 'template_id' => null,
                'award_title' => 'Excellence in Waste Collection Award',
                'award_description' => 'Awarded for demonstrating outstanding commitment to environmental sustainability through active participation in the school waste collection program.',
                'awarded_date' => '2025-01-10', 'issued_by' => 1,
            ],
            [
                'student_id' => 1, 'template_id' => null,
                'award_title' => 'Monthly Top Collector Award',
                'award_description' => 'Awarded to the student with the highest bottle collection count for the month of December.',
                'awarded_date' => '2024-12-01', 'issued_by' => 1,
            ],
            [
                'student_id' => 1, 'template_id' => null,
                'award_title' => 'Participation Certificate',
                'award_description' => 'Awarded for active participation in the EcoCollect school waste management program.',
                'awarded_date' => '2025-01-15', 'issued_by' => 1,
            ],
            [
                'student_id' => 3, 'template_id' => null,
                'award_title' => 'Excellence in Waste Collection Award',
                'award_description' => 'Awarded for demonstrating outstanding commitment to environmental sustainability.',
                'awarded_date' => '2025-01-10', 'issued_by' => 1,
            ],
            [
                'student_id' => 5, 'template_id' => null,
                'award_title' => 'Monthly Top Collector Award',
                'award_description' => 'Awarded to the student with the highest bottle collection count.',
                'awarded_date' => '2024-12-01', 'issued_by' => 1,
            ],
        ];

        foreach ($awards as $a) {
            DB::table('certificate_awards')->insert($a + ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
