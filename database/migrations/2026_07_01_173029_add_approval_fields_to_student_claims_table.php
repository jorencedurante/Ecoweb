<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('student_claims', 'status')) {
                $table->string('status', 20)->default('Pending')->after('remarks');
            }

            if (!Schema::hasColumn('student_claims', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            }

            if (!Schema::hasColumn('student_claims', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('student_claims', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('approved_at');
            }
        });

        // Set existing records with null status to 'Approved'
        DB::table('student_claims')->whereNull('status')->update(['status' => 'Approved']);
        DB::table('student_claims')->where('status', 'approved')->update(['status' => 'Approved']);
        DB::table('student_claims')->where('status', 'pending')->update(['status' => 'Pending']);
        DB::table('student_claims')->where('status', 'rejected')->update(['status' => 'Rejected']);
    }

    public function down(): void
    {
        Schema::table('student_claims', function (Blueprint $table) {
            $table->dropColumn(['rejected_reason', 'approved_at', 'approved_by', 'status']);
        });
    }
};
