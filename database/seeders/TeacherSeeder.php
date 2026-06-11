<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = [
            [
                'admin_id' => 'ADM001', 'user_id' => null, 'name' => 'Juan Dela Cruz',
                'email' => 'juan@ecocollect.edu', 'position' => 'Admin', 'status' => 'active',
            ],
            [
                'admin_id' => 'ADM002', 'user_id' => null, 'name' => 'Maria Santos',
                'email' => 'maria@ecocollect.edu', 'position' => 'Teacher', 'status' => 'active',
            ],
            [
                'admin_id' => 'ADM003', 'user_id' => null, 'name' => 'Pedro Reyes',
                'email' => 'pedro@ecocollect.edu', 'position' => 'Teacher', 'status' => 'active',
            ],
            [
                'admin_id' => 'ADM004', 'user_id' => null, 'name' => 'Ana Gonzales',
                'email' => 'ana@ecocollect.edu', 'position' => 'Admin', 'status' => 'active',
            ],
        ];

        foreach ($teachers as $teacher) {
            $existing = DB::table('teachers')->where('admin_id', $teacher['admin_id'])->first();
            if (!$existing) {
                DB::table('teachers')->insert($teacher + ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
