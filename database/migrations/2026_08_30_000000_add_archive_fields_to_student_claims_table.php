<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_claims', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('status');
            $table->timestamp('released_at')->nullable()->after('is_archived');
            $table->unsignedBigInteger('released_by')->nullable()->after('released_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_claims', function (Blueprint $table) {
            $table->dropColumn(['is_archived', 'released_at', 'released_by']);
        });
    }
};
