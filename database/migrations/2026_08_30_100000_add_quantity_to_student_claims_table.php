<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_claims', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->after('claim_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_claims', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
