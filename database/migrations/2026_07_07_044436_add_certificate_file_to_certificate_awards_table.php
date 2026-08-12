<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_awards', 'certificate_file')) {
                $table->string('certificate_file', 255)->nullable()->after('award_description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificate_awards', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_awards', 'certificate_file')) {
                $table->dropColumn('certificate_file');
            }
        });
    }
};
