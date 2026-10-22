<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'region')) {
                $table->string('region', 100)->nullable()->after('province');
            }
            if (!Schema::hasColumn('students', 'nationality')) {
                $table->string('nationality', 100)->nullable()->after('religion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('students', 'region')) {
                $columns[] = 'region';
            }
            if (Schema::hasColumn('students', 'nationality')) {
                $columns[] = 'nationality';
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
