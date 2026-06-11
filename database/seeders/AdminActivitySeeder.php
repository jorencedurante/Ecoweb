<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['user_id' => 1, 'action' => 'Login', 'description' => 'User logged into the system.', 'module' => 'Auth', 'created_at' => '2026-01-06 08:00:00'],
            ['user_id' => 1, 'action' => 'Added Student', 'description' => 'Added Kathleen E. Tabadero to Grade 6.', 'module' => 'Students', 'created_at' => '2026-01-06 08:30:00'],
            ['user_id' => 1, 'action' => 'Added Bottle Collection', 'description' => 'Recorded 5 bottles for Kathleen E. Tabadero.', 'module' => 'Bottle Collection', 'created_at' => '2026-01-06 08:35:00'],
            ['user_id' => 1, 'action' => 'Generated QR Code', 'description' => 'Generated QR code for Denver P. Tabadero.', 'module' => 'QR Code', 'created_at' => '2026-01-07 10:00:00'],
            ['user_id' => 1, 'action' => 'Logout', 'description' => 'User logged out of the system.', 'module' => 'Auth', 'created_at' => '2026-01-07 17:00:00'],
        ];

        foreach ($activities as $a) {
            DB::table('admin_activities')->insert($a);
        }
    }
}
