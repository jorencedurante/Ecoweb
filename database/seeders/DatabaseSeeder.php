<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            StudentSeeder::class,
            TeacherSeeder::class,
            BottleCollectionSeeder::class,
            AchievementSeeder::class,
            AwardSeeder::class,
            AdminActivitySeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
