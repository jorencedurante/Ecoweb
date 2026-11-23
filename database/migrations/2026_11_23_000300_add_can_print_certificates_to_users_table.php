<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Explicit, opt-in certificate printing permission on the existing users
     * table. Existing accounts keep their current behaviour: no grant means
     * printing is not allowed. No new table is created and no data is changed.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'can_print_certificates')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_print_certificates')->default(false)->after('sub_teacher_permissions');
        });
    }

    public function down(): void
    {
        // Intentionally a no-op: dropping the column could destroy granted
        // permissions. Rollback is handled by a reviewed migration.
    }
};
