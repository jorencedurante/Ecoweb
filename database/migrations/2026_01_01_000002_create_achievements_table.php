<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('badge_name', 100)->nullable();
            $table->string('badge_icon', 50)->nullable();
            $table->integer('milestone')->nullable();
            $table->integer('points_required')->default(0);
            $table->date('achieved_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
