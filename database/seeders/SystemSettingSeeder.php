<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('system_settings')->insert([
            'admin_name' => 'Admin',
            'school_organization' => 'EcoCollect Elementary School',
            'address' => '123 Green Street, Eco City',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
