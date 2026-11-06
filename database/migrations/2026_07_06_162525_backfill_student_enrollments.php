<?php

use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $students = Student::whereNotNull('teacher_id')
            ->where('status', '!=', 'Archived')
            ->get();

        $count = 0;
        foreach ($students as $student) {
            $exists = StudentEnrollment::where('student_id', $student->id)
                ->where('teacher_id', $student->teacher_id)
                ->exists();

            if (!$exists) {
                StudentEnrollment::create([
                    'student_id' => $student->id,
                    'teacher_id' => $student->teacher_id,
                    'grade_level' => $student->grade_level,
                    'status' => 'active',
                ]);
                $count++;
            }
        }

        echo "Created {$count} enrollment records from existing teacher assignments.\n";
    }

    public function down(): void
    {
    }
};
