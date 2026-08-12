<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'pending_email')) {
                $table->string('pending_email')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'email_change_otp')) {
                $table->string('email_change_otp', 255)->nullable()->after('email_verification_code');
            }
            if (!Schema::hasColumn('users', 'email_change_otp_expires_at')) {
                $table->timestamp('email_change_otp_expires_at')->nullable()->after('email_change_otp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pending_email', 'email_change_otp', 'email_change_otp_expires_at']);
        });
    }
};
