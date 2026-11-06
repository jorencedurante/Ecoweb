<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add any users-table columns/lengths required by the application,
     * but only when they are actually missing or too short.
     *
     * The users table holds every account in the system:
     * super_admin, admin and teacher (role = 'teacher').
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 50)->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 30)->default('teacher')->after('password');
            }
            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position', 100)->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status', 30)->default('active')->after('position');
            }
            if (!Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo', 255)->nullable()->after('position');
            }
        });

        foreach (['email_verification_code', 'email_change_otp'] as $column) {
            $length = $this->stringLength('users', $column);

            if ($length === null || $length >= 255 || !Schema::hasColumn('users', $column)) {
                continue;
            }

            Schema::table('users', function (Blueprint $table) use ($column) {
                $table->string($column, 255)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Column additions and length widening are intentionally not reversed.
    }

    private function stringLength(string $table, string $column): ?int
    {
        try {
            $driver = Schema::getConnection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $row = DB::selectOne(
                    'select character_maximum_length as `length`
                     from information_schema.columns
                     where table_schema = database()
                       and table_name = ?
                       and column_name = ?',
                    [$table, $column]
                );

                return $row ? (int) $row->length : null;
            }

            if ($driver === 'sqlite') {
                foreach (DB::select(sprintf('pragma table_info(%s)', $table)) as $info) {
                    if (isset($info->name) && $info->name === $column) {
                        if (preg_match('/\((\d+)\)/', (string) $info->type, $matches)) {
                            return (int) $matches[1];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
};
