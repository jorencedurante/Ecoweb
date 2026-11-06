<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add archive_batch_id to students so that Unarchive All can
     * safely restore only students from a specific Archive All
     * batch without touching individually archived students.
     */
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            if (!Schema::hasColumn('students', 'archive_batch_id')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->string('archive_batch_id', 64)
                        ->nullable()
                        ->after('archive_school_year');
                    $table->index('archive_batch_id');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students')) {
            if (Schema::hasColumn('students', 'archive_batch_id')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->dropIndex(['archive_batch_id']);
                    $table->dropColumn('archive_batch_id');
                });
            }
        }
    }
};
