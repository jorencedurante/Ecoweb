<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeacherSeeder extends Seeder
{
    /**
     * Seed teacher accounts directly into the users table
     * (role = teacher). The legacy teachers table is no longer used.
     */
    public function run(): void
    {
        $teachers = [
            [
                'name' => 'Juan Dela Cruz', 'email' => 'juan@ecocollect.edu',
                'username' => 'juan.delacruz', 'position' => 'Teacher', 'status' => 'active',
            ],
            [
                'name' => 'Maria Santos', 'email' => 'maria@ecocollect.edu',
                'username' => 'maria.santos', 'position' => 'Teacher', 'status' => 'active',
            ],
            [
                'name' => 'Pedro Reyes', 'email' => 'pedro@ecocollect.edu',
                'username' => 'pedro.reyes', 'position' => 'Teacher', 'status' => 'active',
            ],
            [
                'name' => 'Ana Gonzales', 'email' => 'ana@ecocollect.edu',
                'username' => 'ana.gonzales', 'position' => 'Teacher', 'status' => 'active',
            ],
        ];

        foreach ($teachers as $teacher) {
            $existing = DB::table('users')
                ->where('email', $teacher['email'])
                ->orWhere('username', $teacher['username'])
                ->first();

            if (!$existing) {
                DB::table('users')->insert([
                    'name' => $teacher['name'],
                    'username' => $teacher['username'],
                    'email' => $teacher['email'],
                    'password' => Hash::make('password123'),
                    'role' => 'teacher',
                    'position' => $teacher['position'],
                    'status' => $teacher['status'],
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
