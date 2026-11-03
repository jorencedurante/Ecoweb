<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add enrollment_id to bottle_collections, student_claims, and
     * student_achievements so records can be isolated per school-year
     * enrollment instead of only by student_id. All columns are nullable
     * and idempotent-guarded: existing historical rows keep NULL.
     */
    public function up(): void
    {
        if (Schema::hasTable('bottle_collections')
            && !Schema::hasColumn('bottle_collections', 'enrollment_id')) {
            Schema::table('bottle_collections', function (Blueprint $table) {
                $table->unsignedBigInteger('enrollment_id')->nullable()->after('student_id');
                $table->foreign('enrollment_id')
                    ->references('id')->on('student_enrollments')->nullOnDelete();
            });
        }

        if (Schema::hasTable('student_claims')
            && !Schema::hasColumn('student_claims', 'enrollment_id')) {
            Schema::table('student_claims', function (Blueprint $table) {
                $table->unsignedBigInteger('enrollment_id')->nullable()->after('student_id');
                $table->foreign('enrollment_id')
                    ->references('id')->on('student_enrollments')->nullOnDelete();
            });
        }

        if (Schema::hasTable('student_achievements')
            && !Schema::hasColumn('student_achievements', 'enrollment_id')) {
            Schema::table('student_achievements', function (Blueprint $table) {
                $table->unsignedBigInteger('enrollment_id')->nullable()->after('student_id');
                $table->foreign('enrollment_id')
                    ->references('id')->on('student_enrollments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_achievements')
            && Schema::hasColumn('student_achievements', 'enrollment_id')) {
            $fks = collect(Schema::getForeignKeys('student_achievements'))
                ->filter(fn ($fk) => in_array('enrollment_id', $fk['columns'] ?? [], true));
            foreach ($fks as $fk) {
                $name = $fk['constraint'] ?? null;
                if ($name) {
                    Schema::table('student_achievements', function ($t) use ($name) {
                        $t->dropForeign($name);
                    });
                }
            }
            Schema::table('student_achievements', function (Blueprint $table) {
                $table->dropColumn('enrollment_id');
            });
        }

        if (Schema::hasTable('student_claims')
            && Schema::hasColumn('student_claims', 'enrollment_id')) {
            $fks = collect(Schema::getForeignKeys('student_claims'))
                ->filter(fn ($fk) => in_array('enrollment_id', $fk['columns'] ?? [], true));
            foreach ($fks as $fk) {
                $name = $fk['constraint'] ?? null;
                if ($name) {
                    Schema::table('student_claims', function ($t) use ($name) {
                        $t->dropForeign($name);
                    });
                }
            }
            Schema::table('student_claims', function (Blueprint $table) {
                $table->dropColumn('enrollment_id');
            });
        }

        if (Schema::hasTable('bottle_collections')
            && Schema::hasColumn('bottle_collections', 'enrollment_id')) {
            $fks = collect(Schema::getForeignKeys('bottle_collections'))
                ->filter(fn ($fk) => in_array('enrollment_id', $fk['columns'] ?? [], true));
            foreach ($fks as $fk) {
                $name = $fk['constraint'] ?? null;
                if ($name) {
                    Schema::table('bottle_collections', function ($t) use ($name) {
                        $t->dropForeign($name);
                    });
                }
            }
            Schema::table('bottle_collections', function (Blueprint $table) {
                $table->dropColumn('enrollment_id');
            });
        }
    }
};
