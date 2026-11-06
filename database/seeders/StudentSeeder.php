<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            [
                'lrn' => '123456789012', 'first_name' => 'Kathleen', 'middle_name' => 'E.',
                'last_name' => 'Tabadero', 'gender' => 'Female', 'grade_level' => 'Grade 6',
                'qr_code' => 'Q001', 'total_points' => 43, 'status' => 'active',
            ],
            [
                'lrn' => '123456789013', 'first_name' => 'Joy', 'middle_name' => 'O.',
                'last_name' => 'Tabadero', 'gender' => 'Female', 'grade_level' => 'Grade 5',
                'qr_code' => 'Q002', 'total_points' => 38, 'status' => 'active',
            ],
            [
                'lrn' => '123456789014', 'first_name' => 'Jerence', 'middle_name' => 'C.',
                'last_name' => 'Tabadero', 'gender' => 'Male', 'grade_level' => 'Grade 4',
                'qr_code' => 'Q003', 'total_points' => 50, 'status' => 'active',
            ],
            [
                'lrn' => '123456789015', 'first_name' => 'Patricia', 'middle_name' => 'R.',
                'last_name' => 'Tabadero', 'gender' => 'Female', 'grade_level' => 'Grade 3',
                'qr_code' => 'Q004', 'total_points' => 32, 'status' => 'active',
            ],
            [
                'lrn' => '123456789016', 'first_name' => 'Denver', 'middle_name' => 'P.',
                'last_name' => 'Tabadero', 'gender' => 'Male', 'grade_level' => 'Grade 2',
                'qr_code' => 'Q005', 'total_points' => 45, 'status' => 'active',
            ],
            [
                'lrn' => '123456789017', 'first_name' => 'Karen', 'middle_name' => 'N.',
                'last_name' => 'Tabadero', 'gender' => 'Female', 'grade_level' => 'Grade 6',
                'qr_code' => 'Q006', 'total_points' => 40, 'status' => 'active',
            ],
            [
                'lrn' => '123456789018', 'first_name' => 'Sophia', 'middle_name' => 'M.',
                'last_name' => 'Cruz', 'gender' => 'Female', 'grade_level' => 'Kindergarten',
                'qr_code' => 'Q007', 'total_points' => 15, 'status' => 'active',
            ],
        ];

        foreach ($students as $student) {
            $existing = DB::table('students')->where('lrn', $student['lrn'])->first();
            if (!$existing) {
                DB::table('students')->insert($student + ['created_at' => now(), 'updated_at' => now()]);
            } else {
                DB::table('students')->where('id', $existing->id)->update($student + ['updated_at' => now()]);
            }
        }
    }
}
