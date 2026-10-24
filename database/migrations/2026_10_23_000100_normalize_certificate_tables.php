<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalizes certificate_templates + certificate_awards.
 *
 * Target: the LOCAL test database `ecocollect_clean` only.
 * Never run against ecocollect_db or Railway without separate review.
 *
 * Phase A (always, non-destructive): additive backfills only.
 * Phase B (destructive, OPT-IN via CERT_SCHEMA_CLEANUP_APPLY=true):
 *   certificate_awards: drop template_id (+FK), awarded_by, template_file_path
 *   certificate_templates: drop created_by (+FK)
 * Phase C (destructive, OPT-IN via CERT_SCHEMA_CLEANUP_DROP_METADATA=true):
 *   certificate_awards: drop certificate_title, certificate_type, school_year, remarks
 *
 * Every step is hasColumn-guarded and idempotent. See CERTIFICATE_SCHEMA_NORMALIZATION.md
 * for the full code-dependency audit and before/after comparison.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('certificate_awards')) {
            return;
        }

        // ---- Phase A: non-destructive backfills (no-ops when source is empty) ----
        if (Schema::hasColumn('certificate_awards', 'award_title')
            && Schema::hasColumn('certificate_awards', 'certificate_title')) {
            DB::table('certificate_awards')
                ->where(function ($q) {
                    $q->whereNull('award_title')->orWhere('award_title', '');
                })
                ->whereNotNull('certificate_title')
                ->update(['award_title' => DB::raw('certificate_title')]);
        }

        if (Schema::hasColumn('certificate_awards', 'signed_by')
            && Schema::hasColumn('certificate_awards', 'awarded_by')) {
            DB::table('certificate_awards')
                ->whereNull('signed_by')
                ->whereNotNull('awarded_by')
                ->update(['signed_by' => DB::raw('awarded_by')]);
        }

        // ---- Phase B: drop dead duplication (requires explicit approval flag) ----
        if (!env('CERT_SCHEMA_CLEANUP_APPLY', false)) {
            return;
        }

        Schema::table('certificate_awards', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_awards', 'template_id')) {
                $table->dropForeign(['template_id']);
            }
            if (Schema::hasColumn('certificate_awards', 'awarded_by')) {
                $table->dropColumn('awarded_by');
            }
            if (Schema::hasColumn('certificate_awards', 'template_file_path')) {
                $table->dropColumn('template_file_path');
            }
        });

        if (Schema::hasColumn('certificate_awards', 'template_id')) {
            Schema::table('certificate_awards', function (Blueprint $table) {
                $table->dropColumn('template_id');
            });
        }

        if (Schema::hasTable('certificate_templates')
            && Schema::hasColumn('certificate_templates', 'created_by')) {
            Schema::table('certificate_templates', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
            });
            Schema::table('certificate_templates', function (Blueprint $table) {
                $table->dropColumn('created_by');
            });
        }

        // ---- Phase C: drop write-never metadata slots (separate approval flag) ----
        if (!env('CERT_SCHEMA_CLEANUP_DROP_METADATA', false)) {
            return;
        }

        Schema::table('certificate_awards', function (Blueprint $table) {
            $columns = array_values(array_intersect(
                ['certificate_title', 'certificate_type', 'school_year', 'remarks'],
                Schema::getColumnListing('certificate_awards')
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('certificate_awards')) {
            return;
        }

        Schema::table('certificate_awards', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_awards', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('certificate_awards', 'awarded_by')) {
                $table->string('awarded_by', 255)->nullable()->after('certificate_file');
            }
            if (!Schema::hasColumn('certificate_awards', 'template_file_path')) {
                $table->string('template_file_path', 255)->nullable()->after('awarded_by');
            }
            if (!Schema::hasColumn('certificate_awards', 'certificate_title')) {
                $table->string('certificate_title', 255)->nullable()->after('certificate_type');
            }
            if (!Schema::hasColumn('certificate_awards', 'certificate_type')) {
                $table->string('certificate_type', 100)->nullable()->after('awarded_date');
            }
            if (!Schema::hasColumn('certificate_awards', 'school_year')) {
                $table->string('school_year', 50)->nullable()->after('signed_by');
            }
            if (!Schema::hasColumn('certificate_awards', 'remarks')) {
                $table->text('remarks')->nullable()->after('school_year');
            }
        });

        $templateFkExists = collect(Schema::getForeignKeys('certificate_awards'))->contains(function ($fk) {
            return in_array('template_id', $fk['columns'] ?? [], true);
        });
        if (Schema::hasColumn('certificate_awards', 'template_id')
            && !$templateFkExists
            && Schema::hasTable('certificate_templates')) {
            Schema::table('certificate_awards', function ($table) {
                $table->foreign('template_id')
                    ->references('id')->on('certificate_templates')->nullOnDelete();
            });
        }

        if (Schema::hasTable('certificate_templates')) {
            Schema::table('certificate_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('certificate_templates', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }
    }
};
