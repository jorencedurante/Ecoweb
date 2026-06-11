<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_achievements')) {
            Schema::create('student_achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('achievement_quest_id')->constrained('achievements')->cascadeOnDelete();
                $table->date('awarded_date');
                $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['student_id', 'achievement_quest_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_achievements');
    }
};
