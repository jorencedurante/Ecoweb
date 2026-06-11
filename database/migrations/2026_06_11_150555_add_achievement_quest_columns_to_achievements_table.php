<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('achievements', 'required_bottles')) {
                $table->integer('required_bottles')->default(0)->after('milestone');
            }
            if (!Schema::hasColumn('achievements', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('status');
            }
        });

        Schema::table('achievements', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $columns = ['required_bottles', 'created_by'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('achievements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
