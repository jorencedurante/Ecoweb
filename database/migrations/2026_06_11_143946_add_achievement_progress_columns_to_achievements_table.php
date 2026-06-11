<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('achievements', 'current_milestone')) {
                $table->integer('current_milestone')->default(0)->after('badge_name');
            }
            if (!Schema::hasColumn('achievements', 'next_milestone')) {
                $table->integer('next_milestone')->default(50)->after('current_milestone');
            }
            if (!Schema::hasColumn('achievements', 'progress_value')) {
                $table->integer('progress_value')->default(0)->after('next_milestone');
            }
            if (!Schema::hasColumn('achievements', 'status')) {
                $table->string('status')->default('In Progress')->after('progress_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $columns = ['current_milestone', 'next_milestone', 'progress_value', 'status'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('achievements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
