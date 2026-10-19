<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_claims', function (Blueprint $table) {
            // Distinguishes claims whose points and stock were already
            // reserved at submission time (new workflow) from legacy pending
            // claims created before the reservation workflow existed.
            if (!Schema::hasColumn('student_claims', 'is_reserved')) {
                $table->boolean('is_reserved')->default(false)->after('status');
            }

            // Idempotency key so a duplicated form submission (double click,
            // retry) can never create a second reservation.
            if (!Schema::hasColumn('student_claims', 'request_token')) {
                $table->string('request_token', 64)->nullable()->unique()->after('is_reserved');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_claims', function (Blueprint $table) {
            if (Schema::hasColumn('student_claims', 'request_token')) {
                $table->dropUnique(['request_token']);
                $table->dropColumn('request_token');
            }

            if (Schema::hasColumn('student_claims', 'is_reserved')) {
                $table->dropColumn('is_reserved');
            }
        });
    }
};
