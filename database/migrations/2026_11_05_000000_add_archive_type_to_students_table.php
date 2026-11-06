<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add archive_type and archive_school_year to students so the archived
     * students page can distinguish individual archives from school-year
     * bulk archives. Both columns are nullable: existing archived records
     * keep NULL until re-archived or restored.
     */
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            if (!Schema::hasColumn('students', 'archive_type')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->enum('archive_type', ['individual', 'school_year'])
                        ->nullable()
                        ->after('status');
                });
            }
            if (!Schema::hasColumn('students', 'archive_school_year')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->string('archive_school_year', 20)
                        ->nullable()
                        ->after('archive_type');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students')) {
            if (Schema::hasColumn('students', 'archive_school_year')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->dropColumn('archive_school_year');
                });
            }
            if (Schema::hasColumn('students', 'archive_type')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->dropColumn('archive_type');
                });
            }
        }
    }
};
