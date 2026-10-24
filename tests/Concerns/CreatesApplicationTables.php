<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Builds the base application tables for tests.
 *
 * The project has no "create table" migrations for its original tables
 * (they were created from a SQL dump), so tests create the schema they
 * need. Every table is guarded with Schema::hasTable so this is a no-op
 * when the tests run against a MySQL database that already has the schema.
 */
trait CreatesApplicationTables
{
    protected function createApplicationTables(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('username', 50)->nullable()->unique();
                $table->string('email', 255)->unique();
                $table->string('pending_email', 255)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password', 255);
                $table->string('role', 30)->default('teacher');
                $table->string('position', 100)->nullable();
                $table->string('profile_photo', 255)->nullable();
                $table->string('status', 30)->default('active');
                $table->string('email_verification_code', 255)->nullable();
                $table->string('email_change_otp', 255)->nullable();
                $table->timestamp('email_change_otp_expires_at')->nullable();
                $table->timestamp('email_verification_expires_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('teachers')) {
            Schema::create('teachers', function (Blueprint $table) {
                $table->id();
                $table->string('admin_id', 50)->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name', 150);
                $table->string('email', 255)->nullable();
                $table->string('position', 100)->nullable();
                $table->string('status', 30)->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->string('student_id', 50)->nullable()->unique();
                $table->string('lrn', 50)->unique();
                $table->string('first_name', 100);
                $table->string('middle_name', 100)->nullable();
                $table->string('last_name', 100);
                $table->string('full_name', 255)->nullable();
                $table->string('gender', 20)->nullable();
                $table->date('birth_date')->nullable();
                $table->integer('age')->nullable();
                $table->string('grade_level', 50)->nullable();
                $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('mother_tongue', 100)->nullable();
                $table->string('ip_ethnic_group', 150)->nullable();
                $table->string('religion', 100)->nullable();
                $table->string('nationality', 100)->nullable();
                $table->string('house_street', 255)->nullable();
                $table->string('barangay', 150)->nullable();
                $table->string('municipality_city', 150)->nullable();
                $table->string('province', 150)->nullable();
                $table->string('region', 100)->nullable();
                $table->string('father_name', 255)->nullable();
                $table->string('mother_maiden_name', 255)->nullable();
                $table->string('guardian_name', 255)->nullable();
                $table->string('guardian_relationship', 150)->nullable();
                $table->string('contact_number', 40)->nullable();
                $table->string('learning_modality', 100)->nullable();
                $table->text('remarks')->nullable();
                $table->string('qr_code', 255)->nullable()->unique();
                $table->integer('total_points')->default(0);
                $table->string('status', 30)->default('Active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('student_enrollments')) {
            Schema::create('student_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->string('grade_level', 50)->nullable();
                $table->string('section', 100)->nullable();
                $table->string('school_year', 20)->nullable();
                $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('imported_from_file', 255)->nullable();
                $table->string('status')->default('active');
                $table->timestamps();

                $table->unique(
                    ['student_id', 'teacher_id', 'grade_level', 'section', 'school_year'],
                    'unique_enrollment'
                );
            });
        }

        if (!Schema::hasTable('bottle_collections')) {
            Schema::create('bottle_collections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('lrn', 50)->nullable();
                $table->date('collection_date');
                $table->time('collection_time')->nullable();
                $table->integer('bottle_count')->default(0);
                $table->integer('points_earned')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('qr_codes')) {
            Schema::create('qr_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
                $table->string('student_name', 150)->nullable();
                $table->string('qr_type', 50)->default('LRN');
                $table->string('qr_value', 255)->unique();
                $table->string('qr_image_path', 255)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('certificate_templates')) {
            Schema::create('certificate_templates', function (Blueprint $table) {
                $table->id();
                $table->string('template_name', 150);
                $table->text('description')->nullable();
                $table->string('file_path', 255);
                $table->string('file_type', 50)->default('docx');
                $table->string('status', 50)->default('active');
                $table->string('visibility', 50)->default('private');
                $table->string('template_type', 50)->default('uploaded');
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('certificate_awards')) {
            Schema::create('certificate_awards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
                $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('award_title', 150);
                $table->text('award_description')->nullable();
                $table->string('certificate_file', 255)->nullable();
                $table->string('template_file', 255)->nullable();
                $table->longText('canvas_data')->nullable();
                $table->date('awarded_date');
                $table->string('generated_file_path', 255)->nullable();
                $table->string('pdf_file_path', 255)->nullable();
                $table->string('status', 50)->default('Active');
                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('signed_by', 255)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('achievements')) {
            Schema::create('achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
                $table->string('title', 150);
                $table->text('description')->nullable();
                $table->string('badge_name', 150)->nullable();
                $table->integer('current_milestone')->default(0);
                $table->integer('next_milestone')->default(50);
                $table->integer('progress_value')->default(0);
                $table->string('status', 50)->default('In Progress');
                $table->foreignId('created_by')->nullable();
                $table->string('milestone', 150)->nullable();
                $table->integer('required_bottles')->default(0);
                $table->integer('points_required')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('student_achievements')) {
            Schema::create('student_achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('achievement_quest_id')->constrained('achievements')->cascadeOnDelete();
                $table->date('awarded_date');
                $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['student_id', 'achievement_quest_id'], 'unique_student_quest');
            });
        }

        if (!Schema::hasTable('claim_items')) {
            Schema::create('claim_items', function (Blueprint $table) {
                $table->id();
                $table->string('item_name', 255);
                $table->text('description')->nullable();
                $table->integer('points_required')->default(0);
                $table->integer('quantity')->default(0);
                $table->string('status', 50)->default('Available');
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('student_claims')) {
            Schema::create('student_claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('claim_item_id')->nullable();
                $table->integer('quantity')->default(1);
                $table->string('item_name', 255);
                $table->integer('points_deducted')->default(0);
                $table->integer('points_before')->default(0);
                $table->integer('points_after')->default(0);
                $table->date('claim_date');
                $table->foreignId('claimed_by')->nullable();
                $table->text('remarks')->nullable();
                $table->string('status', 20)->default('approved');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejected_reason')->nullable();
                $table->boolean('is_archived')->default(false);
                $table->timestamp('released_at')->nullable();
                $table->boolean('is_reserved')->default(false);
                $table->string('request_token', 64)->nullable()->unique();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('student_claims', 'is_reserved') || !Schema::hasColumn('student_claims', 'request_token')) {
            Schema::table('student_claims', function (Blueprint $table) {
                if (!Schema::hasColumn('student_claims', 'is_reserved')) {
                    $table->boolean('is_reserved')->default(false);
                }

                if (!Schema::hasColumn('student_claims', 'request_token')) {
                    $table->string('request_token', 64)->nullable()->unique();
                }
            });
        }

        if (!Schema::hasTable('admin_activities')) {
            Schema::create('admin_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 150);
                $table->text('description')->nullable();
                $table->string('module', 100)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('admin_name', 150)->nullable();
                $table->string('school_organization', 150)->nullable();
                $table->string('school_name', 150)->nullable();
                $table->string('organization_name', 150)->nullable();
                $table->text('address')->nullable();
                $table->string('system_logo', 255)->nullable();
                $table->boolean('email_notifications')->default(true);
                $table->boolean('bottle_collection_reports')->default(true);
                $table->boolean('system_alerts')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('notification_settings')) {
            Schema::create('notification_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('bottle_collection_reports')->default(true);
                $table->boolean('system_alerts')->default(true);
                $table->boolean('email_notifications')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }
}
