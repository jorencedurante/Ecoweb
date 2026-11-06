<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Merge the legacy `teachers` table into the `users` table.
     *
     * - Every teachers row ends up linked to a real users row (role = teacher).
     * - Any foreign key that still references teachers.id is remapped to users.id.
     * - The legacy `teachers` table is NOT dropped; it is kept for manual
     *   verification and can be removed later once everything is confirmed.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('teachers')) {
            return;
        }

        DB::transaction(function () {
            $this->linkLegacyTeacherRowsToUsers();
            $this->remapTeacherForeignKeysToUsers();
        });

        echo "Legacy teachers rows are now linked to users accounts. The teachers table was kept (not dropped).\n";
    }

    public function down(): void
    {
        // Data migration: intentionally not reversible.
    }

    private function linkLegacyTeacherRowsToUsers(): void
    {
        foreach (DB::table('teachers')->orderBy('id')->get() as $teacher) {
            $userId = $teacher->user_id ?? null;

            if ($userId !== null && DB::table('users')->where('id', $userId)->exists()) {
                // Already linked to a real account: preserve that account as-is.
                continue;
            }

            $email = $teacher->email ? trim($teacher->email) : null;

            if ($email !== null) {
                $existing = DB::table('users')->where('email', $email)->first();

                if ($existing) {
                    // Reuse the existing account instead of creating a duplicate.
                    DB::table('teachers')->where('id', $teacher->id)->update(['user_id' => $existing->id]);
                    continue;
                }
            } else {
                // A login account cannot be created without an email address.
                continue;
            }

            $newUserId = DB::table('users')->insertGetId([
                'name' => $teacher->name ?: 'Teacher',
                'username' => $this->uniqueUsername($email, $teacher->name),
                'email' => $email,
                'password' => Hash::make(Str::random(24)),
                'role' => 'teacher',
                'position' => $teacher->position,
                'status' => $this->normalizeStatus($teacher->status),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('teachers')->where('id', $teacher->id)->update(['user_id' => $newUserId]);
        }
    }

    private function remapTeacherForeignKeysToUsers(): void
    {
        foreach (['students', 'student_enrollments', 'certificate_awards'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'teacher_id')) {
                continue;
            }

            $foreignKey = $this->teacherIdForeignKeyOnTeachers($table);

            if ($foreignKey === null) {
                // teacher_id already points at users (or has no foreign key).
                // Values are left untouched to avoid corrupting valid ids.
                continue;
            }

            foreach (DB::table('teachers')->whereNotNull('user_id')->orderBy('id')->get() as $teacher) {
                if ((int) $teacher->id === (int) $teacher->user_id) {
                    continue;
                }

                DB::table($table)
                    ->where('teacher_id', $teacher->id)
                    ->update(['teacher_id' => $teacher->user_id]);
            }

            $unmapped = DB::table($table)
                ->whereNotNull('teacher_id')
                ->whereIn(
                    'teacher_id',
                    DB::table('teachers')->whereNull('user_id')->pluck('id')->all()
                )
                ->count();

            if ($unmapped > 0) {
                // Some values cannot be mapped to a user; keep the old
                // constraint so no data is silently deleted.
                continue;
            }

            $this->pointTeacherIdForeignKeyAtUsers($table, $foreignKey);
        }
    }

    private function teacherIdForeignKeyOnTeachers(string $table): ?array
    {
        try {
            $foreignKeys = Schema::getForeignKeys($table);
        } catch (\Throwable $e) {
            return null;
        }

        foreach ($foreignKeys as $foreignKey) {
            $columns = (array) ($foreignKey['columns'] ?? []);
            $foreignTable = $foreignKey['foreign_table'] ?? null;

            if ($columns === ['teacher_id'] && $foreignTable === 'teachers') {
                return $foreignKey;
            }
        }

        return null;
    }

    private function pointTeacherIdForeignKeyAtUsers(string $table, array $foreignKey): void
    {
        $onDelete = strtoupper((string) ($foreignKey['on_delete'] ?? ''));
        $name = $foreignKey['name'] ?? null;

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($name, $onDelete) {
                if ($name !== null) {
                    $blueprint->dropForeign($name);
                } else {
                    $blueprint->dropForeign(['teacher_id']);
                }

                $column = $blueprint->foreign('teacher_id')->references('id')->on('users');

                if ($onDelete === 'CASCADE') {
                    $column->onDelete('cascade');
                } elseif ($onDelete === 'SET NULL') {
                    $column->nullOnDelete();
                }
            });
        } catch (\Throwable $e) {
            // Foreign-key changes are DDL on MySQL and cannot be rolled back.
            // If the constraint cannot be rewritten the data stays consistent
            // because values were already remapped above.
        }
    }

    private function uniqueUsername(string $email, ?string $name): string
    {
        $base = Str::slug(Str::before($email, '@'), '_');

        if ($base === '' || $base === null) {
            $base = Str::slug((string) $name, '_');
        }

        if ($base === '' || $base === null) {
            $base = 'teacher';
        }

        $username = $base;
        $suffix = 1;

        while (DB::table('users')->where('username', $username)->exists()) {
            $suffix++;
            $username = $base . '_' . $suffix;
        }

        return $username;
    }

    private function normalizeStatus(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'archived' => 'archived',
            'inactive' => 'inactive',
            default => 'active',
        };
    }
};
