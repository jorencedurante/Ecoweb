<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_awards', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('student_id');
                $table->foreign('template_id')->references('id')->on('certificate_templates')->nullOnDelete();
            }
            if (!Schema::hasColumn('certificate_awards', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('template_id');
                $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('certificate_awards', 'signed_by')) {
                $table->string('signed_by', 255)->nullable()->after('issued_by');
            }
            if (!Schema::hasColumn('certificate_awards', 'signatory_position')) {
                $table->string('signatory_position', 255)->nullable()->after('signed_by');
            }
            if (!Schema::hasColumn('certificate_awards', 'school_year')) {
                $table->string('school_year', 50)->nullable()->after('signatory_position');
            }
            if (!Schema::hasColumn('certificate_awards', 'remarks')) {
                $table->text('remarks')->nullable()->after('school_year');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropForeign(['teacher_id']);
            $table->dropColumn(['template_id', 'teacher_id', 'signed_by', 'signatory_position', 'school_year', 'remarks']);
        });
    }
};
