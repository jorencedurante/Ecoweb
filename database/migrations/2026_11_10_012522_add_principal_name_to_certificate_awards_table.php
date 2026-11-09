<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('certificate_awards', 'principal_name')) {
            Schema::table('certificate_awards', function (Blueprint $table) {
                $table->string('principal_name', 255)->nullable()->after('signed_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('certificate_awards', 'principal_name')) {
            Schema::table('certificate_awards', function (Blueprint $table) {
                $table->dropColumn('principal_name');
            });
        }
    }
};
