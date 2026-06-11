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
        Schema::table('qr_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('qr_codes', 'student_name')) {
                $table->string('student_name', 150)->nullable()->after('student_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            if (Schema::hasColumn('qr_codes', 'student_name')) {
                $table->dropColumn('student_name');
            }
        });
    }
};
