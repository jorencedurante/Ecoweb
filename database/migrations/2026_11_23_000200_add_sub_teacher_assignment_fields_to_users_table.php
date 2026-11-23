<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store Sub Teacher assignments directly on the existing users table.
     * One Sub Teacher -> one assigned Teacher; one Teacher -> many Sub Teachers.
     * No new table is created and no existing user/student data is changed.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'assigned_teacher_id')) {
                $table->foreignId('assigned_teacher_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('status');
            }
            if (!Schema::hasColumn('users', 'sub_teacher_access_level')) {
                $table->string('sub_teacher_access_level', 30)->nullable()->after('assigned_teacher_id');
            }
            if (!Schema::hasColumn('users', 'sub_teacher_permissions')) {
                $table->text('sub_teacher_permissions')->nullable()->after('sub_teacher_access_level');
            }
        });
    }

    public function down(): void
    {
        // Intentionally a no-op: dropping assignment columns could destroy
        // configuration data. Rollback is handled by a reviewed migration.
    }
};
