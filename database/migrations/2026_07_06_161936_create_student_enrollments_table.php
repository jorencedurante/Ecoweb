<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('grade_level', 50)->nullable();
            $table->string('section', 100)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('imported_from_file')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['student_id', 'teacher_id', 'grade_level', 'section', 'school_year'], 'unique_enrollment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
