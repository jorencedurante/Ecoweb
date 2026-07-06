<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('students', 'age')) {
                $table->integer('age')->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('students', 'mother_tongue')) {
                $table->string('mother_tongue', 100)->nullable()->after('age');
            }
            if (!Schema::hasColumn('students', 'ip_ethnic_group')) {
                $table->string('ip_ethnic_group', 100)->nullable()->after('mother_tongue');
            }
            if (!Schema::hasColumn('students', 'religion')) {
                $table->string('religion', 100)->nullable()->after('ip_ethnic_group');
            }
            if (!Schema::hasColumn('students', 'house_street')) {
                $table->string('house_street', 255)->nullable()->after('religion');
            }
            if (!Schema::hasColumn('students', 'barangay')) {
                $table->string('barangay', 100)->nullable()->after('house_street');
            }
            if (!Schema::hasColumn('students', 'municipality_city')) {
                $table->string('municipality_city', 100)->nullable()->after('barangay');
            }
            if (!Schema::hasColumn('students', 'province')) {
                $table->string('province', 100)->nullable()->after('municipality_city');
            }
            if (!Schema::hasColumn('students', 'father_name')) {
                $table->string('father_name', 100)->nullable()->after('province');
            }
            if (!Schema::hasColumn('students', 'mother_maiden_name')) {
                $table->string('mother_maiden_name', 100)->nullable()->after('father_name');
            }
            if (!Schema::hasColumn('students', 'guardian_name')) {
                $table->string('guardian_name', 100)->nullable()->after('mother_maiden_name');
            }
            if (!Schema::hasColumn('students', 'guardian_relationship')) {
                $table->string('guardian_relationship', 100)->nullable()->after('guardian_name');
            }
            if (!Schema::hasColumn('students', 'contact_number')) {
                $table->string('contact_number', 50)->nullable()->after('guardian_relationship');
            }
            if (!Schema::hasColumn('students', 'learning_modality')) {
                $table->string('learning_modality', 100)->nullable()->after('contact_number');
            }
            if (!Schema::hasColumn('students', 'remarks')) {
                $table->text('remarks')->nullable()->after('learning_modality');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'birth_date', 'age', 'mother_tongue', 'ip_ethnic_group', 'religion',
                'house_street', 'barangay', 'municipality_city', 'province',
                'father_name', 'mother_maiden_name', 'guardian_name', 'guardian_relationship',
                'contact_number', 'learning_modality', 'remarks',
            ]);
        });
    }
};
