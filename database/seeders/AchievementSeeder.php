<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            [
                'student_id' => 1, 'title' => 'Top Collector of the Week',
                'description' => 'Awarded for collecting the most bottles in a single week.',
                'badge_name' => 'Gold', 'badge_icon' => '🥇', 'milestone' => 20, 'points_required' => 20, 'achieved_date' => '2025-01-06',
            ],
            [
                'student_id' => 1, 'title' => '100 Bottles Collected',
                'description' => 'Milestone award for reaching 100 total bottle collections.',
                'badge_name' => 'Trophy', 'badge_icon' => '🏆', 'milestone' => 100, 'points_required' => 100, 'achieved_date' => '2024-12-15',
            ],
            [
                'student_id' => 1, 'title' => 'Consistent Recycler',
                'description' => 'Awarded for consistent daily bottle collection participation.',
                'badge_name' => 'Recycle', 'badge_icon' => '♻️', 'milestone' => null, 'points_required' => 0, 'achieved_date' => '2025-01-01',
            ],
            [
                'student_id' => 1, 'title' => 'Eco Champion',
                'description' => 'Highest honor for outstanding environmental leadership.',
                'badge_name' => 'Star', 'badge_icon' => '🌟', 'milestone' => null, 'points_required' => 50, 'achieved_date' => '2024-11-20',
            ],
            [
                'student_id' => 3, 'title' => 'Top Collector of the Week',
                'description' => 'Awarded for collecting the most bottles in a single week.',
                'badge_name' => 'Gold', 'badge_icon' => '🥇', 'milestone' => 25, 'points_required' => 25, 'achieved_date' => '2025-01-08',
            ],
            [
                'student_id' => 5, 'title' => '50 Bottles Collected',
                'description' => 'Milestone award for reaching 50 total bottle collections.',
                'badge_name' => 'Silver', 'badge_icon' => '🥈', 'milestone' => 50, 'points_required' => 50, 'achieved_date' => '2025-01-07',
            ],
        ];

        foreach ($achievements as $a) {
            DB::table('achievements')->insert($a + ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
