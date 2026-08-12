<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_awards', 'canvas_data')) {
                $table->longText('canvas_data')->nullable()->after('template_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_awards', 'canvas_data')) {
                $table->dropColumn('canvas_data');
            }
        });
    }
};
