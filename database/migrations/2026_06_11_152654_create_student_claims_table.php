<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_claims')) {
            Schema::create('student_claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('claim_item_id')->nullable()->constrained('claim_items')->nullOnDelete();
                $table->string('item_name');
                $table->integer('points_deducted')->default(0);
                $table->integer('points_before')->default(0);
                $table->integer('points_after')->default(0);
                $table->date('claim_date');
                $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_claims');
    }
};
