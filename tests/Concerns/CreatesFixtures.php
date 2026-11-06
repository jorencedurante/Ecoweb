<?php

namespace Tests\Concerns;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Str;

trait CreatesFixtures
{
    protected function createSuperAdmin(array $overrides = []): User
    {
        return $this->createAccount(array_merge([
            'name' => 'Super Admin',
            'role' => 'super_admin',
        ], $overrides));
    }

    protected function createAdmin(array $overrides = []): User
    {
        return $this->createAccount(array_merge([
            'name' => 'Admin User',
            'role' => 'admin',
        ], $overrides));
    }

    protected function createTeacher(array $overrides = []): User
    {
        return $this->createAccount(array_merge([
            'name' => 'Teacher User',
            'role' => 'teacher',
        ], $overrides));
    }

    protected function createAccount(array $overrides = []): User
    {
        static $sequence = 0;
        $sequence++;

        return User::create(array_merge([
            'name' => 'Test User ' . $sequence,
            'username' => 'testuser' . $sequence . '_' . Str::random(4),
            'email' => 'testuser' . $sequence . '_' . Str::random(4) . '@example.com',
            'password' => 'secret-password',
            'role' => 'teacher',
            'position' => 'Teacher',
            'status' => 'active',
            'email_verified_at' => now(),
        ], $overrides));
    }

    protected function createStudent(User $teacher = null, array $overrides = []): Student
    {
        static $sequence = 0;
        $sequence++;

        $suffix = random_int(100000, 999999) . $sequence;
        $lrn = '8' . $suffix;

        $student = Student::create(array_merge([
            'student_id' => 'STU' . $suffix,
            'lrn' => $lrn,
            'first_name' => 'Student' . $sequence,
            'last_name' => 'LastName' . $sequence,
            'full_name' => 'Student' . $sequence . ' LastName' . $sequence,
            'grade_level' => 'Grade 1',
            'gender' => 'Male',
            'qr_code' => 'QR-' . $lrn,
            'total_points' => 0,
            'status' => 'Active',
        ], $overrides));

        if ($teacher) {
            $student->forceFill(['teacher_id' => $teacher->id])->save();

            StudentEnrollment::create([
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'grade_level' => $student->grade_level,
                'status' => 'active',
            ]);
        }

        return $student;
    }

    protected function verifyEmailFor(User $user): void
    {
        $user->forceFill(['email_verified_at' => now()])->save();
    }
}
