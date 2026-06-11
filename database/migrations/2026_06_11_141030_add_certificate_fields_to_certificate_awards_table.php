<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_awards', 'certificate_title')) {
                $table->string('certificate_title', 255)->nullable()->after('certificate_type');
            }
            if (!Schema::hasColumn('certificate_awards', 'school_principal_name')) {
                $table->string('school_principal_name', 255)->nullable()->after('award_description');
            }
            if (!Schema::hasColumn('certificate_awards', 'program_coordinator_name')) {
                $table->string('program_coordinator_name', 255)->nullable()->after('school_principal_name');
            }
            if (!Schema::hasColumn('certificate_awards', 'awarded_by')) {
                $table->string('awarded_by', 255)->nullable()->after('program_coordinator_name');
            }
            if (!Schema::hasColumn('certificate_awards', 'template_file_path')) {
                $table->string('template_file_path', 255)->nullable()->after('awarded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            $columns = ['certificate_title', 'school_principal_name', 'program_coordinator_name', 'awarded_by', 'template_file_path'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('certificate_awards', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
