<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_templates', 'visibility')) {
                $table->string('visibility', 50)->default('private')->after('status');
            }
            if (!Schema::hasColumn('certificate_templates', 'template_type')) {
                $table->string('template_type', 50)->default('uploaded')->after('visibility');
            }
            if (!Schema::hasColumn('certificate_templates', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('template_type');
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['visibility', 'template_type', 'created_by']);
        });
    }
};
