<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_awards', 'show_logo')) {
                $table->boolean('show_logo')->default(false)->after('template_file_path');
            }
            if (!Schema::hasColumn('certificate_awards', 'show_certificate_title')) {
                $table->boolean('show_certificate_title')->default(false)->after('show_logo');
            }
            if (!Schema::hasColumn('certificate_awards', 'show_student_name')) {
                $table->boolean('show_student_name')->default(true)->after('show_certificate_title');
            }
            if (!Schema::hasColumn('certificate_awards', 'show_award_description')) {
                $table->boolean('show_award_description')->default(false)->after('show_student_name');
            }
            if (!Schema::hasColumn('certificate_awards', 'show_award_date')) {
                $table->boolean('show_award_date')->default(false)->after('show_award_description');
            }
            if (!Schema::hasColumn('certificate_awards', 'show_principal_name')) {
                $table->boolean('show_principal_name')->default(false)->after('show_award_date');
            }
            if (!Schema::hasColumn('certificate_awards', 'show_program_coordinator_name')) {
                $table->boolean('show_program_coordinator_name')->default(false)->after('show_principal_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            $columns = ['show_logo', 'show_certificate_title', 'show_student_name', 'show_award_description', 'show_award_date', 'show_principal_name', 'show_program_coordinator_name'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('certificate_awards', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
