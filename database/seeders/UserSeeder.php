<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Update existing admin user with proper password hash, or create if not exists
        $admin = DB::table('users')->where('email', 'admin@ecocollect.com')->first();
        if ($admin) {
            DB::table('users')->where('id', $admin->id)->update([
                'name' => 'Admin',
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
                'position' => 'System Administrator',
                'status' => 'active',
            ]);
        } else {
            DB::table('users')->insert([
                'name' => 'Admin',
                'email' => 'admin@ecocollect.com',
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
                'position' => 'System Administrator',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Update existing example admin
        $existing = DB::table('users')->where('email', 'admin@example.com')->first();
        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update([
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
            ]);
        }

        // Update existing teacher user
        $teacher = DB::table('users')->where('email', 'joyballesteros@gmail.com')->first();
        if ($teacher) {
            DB::table('users')->where('id', $teacher->id)->update([
                'password' => Hash::make('password123'),
            ]);
        }
    }
}
