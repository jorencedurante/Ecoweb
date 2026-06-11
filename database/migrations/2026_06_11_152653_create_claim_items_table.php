<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('claim_items')) {
            Schema::create('claim_items', function (Blueprint $table) {
                $table->id();
                $table->string('item_name');
                $table->text('description')->nullable();
                $table->integer('points_required')->default(0);
                $table->integer('quantity')->default(0);
                $table->string('status')->default('Available');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_items');
    }
};
