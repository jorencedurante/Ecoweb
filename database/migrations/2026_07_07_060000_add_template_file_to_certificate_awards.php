<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_awards', 'template_file')) {
                $table->string('template_file', 255)->nullable()->after('template_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_awards', 'template_file')) {
                $table->dropColumn('template_file');
            }
        });
    }
};
