-- =============================================================================
-- CLEAN_DATABASE.sql — EcoCollect clean schema (FOR REVIEW — not yet applied)
-- =============================================================================
-- Schema ONLY. Contains no INSERT/DROP/TRUNCATE/DELETE/ALTER statements.
-- Target: an EMPTY schema (recommended: the already-empty `ecocollect_clean`
-- that .env points at).
--
--   mysql -u root ecocollect_clean < CLEAN_DATABASE.sql
--
-- Do NOT run against `ecocollect_db` (contains the real data).
-- Source docs: CLEAN_DATABASE_SCHEMA.md, CLEAN_DATABASE_REVIEW.md,
-- CLEAN_DATABASE_MIGRATION_PLAN.md
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. framework
-- -----------------------------------------------------------------------------
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. accounts (legacy `teachers` table removed — roles live on users)
-- -----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `pending_email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'teacher',
  `position` varchar(100) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `email_verification_code` varchar(255) DEFAULT NULL,
  `email_change_otp` varchar(255) DEFAULT NULL,
  `email_change_otp_expires_at` timestamp NULL DEFAULT NULL,
  `email_verification_expires_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. students (all columns kept; ENUMs normalized to VARCHAR)
-- -----------------------------------------------------------------------------
CREATE TABLE `students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) DEFAULT NULL,
  `lrn` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `grade_level` varchar(50) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `age` int DEFAULT NULL,
  `mother_tongue` varchar(100) DEFAULT NULL,
  `ip_ethnic_group` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `house_street` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `municipality_city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_maiden_name` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_relationship` varchar(100) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `learning_modality` varchar(100) DEFAULT NULL,
  `remarks` text,
  `qr_code` varchar(255) DEFAULT NULL,
  `total_points` int NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `teacher_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_lrn_unique` (`lrn`),
  UNIQUE KEY `students_student_id_unique` (`student_id`),
  UNIQUE KEY `students_qr_code_unique` (`qr_code`),
  KEY `idx_students_status` (`status`),
  KEY `idx_students_grade_level` (`grade_level`),
  KEY `idx_students_full_name` (`full_name`),
  CONSTRAINT `students_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. teacher <-> student assignments (authoritative; imported_by/from_file are used)
-- -----------------------------------------------------------------------------
CREATE TABLE `student_enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `imported_by` bigint unsigned DEFAULT NULL,
  `imported_from_file` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`student_id`,`teacher_id`,`grade_level`,`section`,`school_year`),
  KEY `student_enrollments_teacher_id_foreign` (`teacher_id`),
  KEY `student_enrollments_imported_by_foreign` (`imported_by`),
  CONSTRAINT `student_enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_enrollments_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_enrollments_imported_by_foreign` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. collections (IoT + manual); new date indexes for dashboard/report queries
-- -----------------------------------------------------------------------------
CREATE TABLE `bottle_collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `lrn` varchar(50) NOT NULL,
  `collection_date` date NOT NULL,
  `collection_time` time DEFAULT NULL,
  `bottle_count` int NOT NULL DEFAULT 0,
  `points_earned` int NOT NULL DEFAULT 0,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bottle_collections_collection_date` (`collection_date`),
  KEY `idx_bottle_collections_student_date` (`student_id`,`collection_date`),
  KEY `bottle_collections_created_by_foreign` (`created_by`),
  CONSTRAINT `bottle_collections_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bottle_collections_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. QR registry
-- -----------------------------------------------------------------------------
CREATE TABLE `qr_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned DEFAULT NULL,
  `student_name` varchar(150) DEFAULT NULL,
  `qr_type` varchar(50) NOT NULL DEFAULT 'LRN',
  `qr_value` varchar(255) NOT NULL,
  `qr_image_path` varchar(255) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_codes_qr_value_unique` (`qr_value`),
  KEY `qr_codes_student_id_foreign` (`student_id`),
  KEY `qr_codes_created_by_foreign` (`created_by`),
  CONSTRAINT `qr_codes_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qr_codes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. certificate templates (normalized: uploader kept as `uploaded_by`;
--    redundant `created_by` removed — always duplicated `uploaded_by`)
-- -----------------------------------------------------------------------------
CREATE TABLE `certificate_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_name` varchar(150) NOT NULL,
  `description` text,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL DEFAULT 'docx',
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `visibility` varchar(50) NOT NULL DEFAULT 'private',
  `template_type` varchar(50) NOT NULL DEFAULT 'uploaded',
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certificate_templates_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `certificate_templates_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. certificate/award table (REUSED per requirement — no new table)
--    Normalized 2026-10-23 (see CERTIFICATE_SCHEMA_NORMALIZATION.md):
--    canonical file = pdf_file_path, canonical issuer = issued_by.
--    Removed 17 legacy/unused columns total: school_principal_name,
--    program_coordinator_name, signatory_position, 7x show_*,
--    template_id (dead FK), awarded_by (text dup), template_file_path,
--    certificate_title, certificate_type, school_year, remarks.
-- -----------------------------------------------------------------------------
CREATE TABLE `certificate_awards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned DEFAULT NULL,
  `teacher_id` bigint unsigned DEFAULT NULL,
  `award_title` varchar(150) NOT NULL,
  `award_description` text,
  `certificate_file` varchar(255) DEFAULT NULL,
  `template_file` varchar(255) DEFAULT NULL,
  `canvas_data` longtext,
  `awarded_date` date NOT NULL,
  `generated_file_path` varchar(255) DEFAULT NULL,
  `pdf_file_path` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Active',
  `issued_by` bigint unsigned DEFAULT NULL,
  `signed_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_certificate_awards_awarded_date` (`awarded_date`),
  KEY `idx_certificate_awards_status` (`status`),
  KEY `certificate_awards_student_id_foreign` (`student_id`),
  KEY `certificate_awards_teacher_id_foreign` (`teacher_id`),
  KEY `certificate_awards_issued_by_foreign` (`issued_by`),
  CONSTRAINT `certificate_awards_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificate_awards_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificate_awards_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. achievements (quest definitions) — created_by FK added (was unconstrained)
-- -----------------------------------------------------------------------------
CREATE TABLE `achievements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `badge_name` varchar(150) DEFAULT NULL,
  `current_milestone` int DEFAULT 0,
  `next_milestone` int DEFAULT 50,
  `progress_value` int DEFAULT 0,
  `status` varchar(50) DEFAULT 'In Progress',
  `created_by` bigint unsigned DEFAULT NULL,
  `milestone` varchar(150) DEFAULT NULL,
  `required_bottles` int DEFAULT 0,
  `points_required` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `achievements_student_id_foreign` (`student_id`),
  KEY `achievements_created_by_foreign` (`created_by`),
  CONSTRAINT `achievements_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `achievements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. earned achievements
-- -----------------------------------------------------------------------------
CREATE TABLE `student_achievements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `achievement_quest_id` bigint unsigned NOT NULL,
  `awarded_date` date NOT NULL,
  `awarded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_quest` (`student_id`,`achievement_quest_id`),
  KEY `student_achievements_achievement_quest_id_foreign` (`achievement_quest_id`),
  KEY `student_achievements_awarded_by_foreign` (`awarded_by`),
  CONSTRAINT `student_achievements_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_achievements_achievement_quest_id_foreign` FOREIGN KEY (`achievement_quest_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_achievements_awarded_by_foreign` FOREIGN KEY (`awarded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. reward catalog — created_by FK added (was unconstrained)
-- -----------------------------------------------------------------------------
CREATE TABLE `claim_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `description` text,
  `points_required` int DEFAULT 0,
  `quantity` int DEFAULT 0,
  `status` varchar(50) DEFAULT 'Available',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_claim_items_status` (`status`),
  KEY `claim_items_created_by_foreign` (`created_by`),
  CONSTRAINT `claim_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 12. claims/reservations — 4 FKs added (student_id, claim_item_id, claimed_by,
--     released_by were entirely unconstrained in the live DB)
-- -----------------------------------------------------------------------------
CREATE TABLE `student_claims` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `claim_item_id` bigint unsigned DEFAULT NULL,
  `quantity` int unsigned NOT NULL DEFAULT 1,
  `item_name` varchar(255) NOT NULL,
  `points_deducted` int DEFAULT 0,
  `points_before` int DEFAULT 0,
  `points_after` int DEFAULT 0,
  `claim_date` date NOT NULL,
  `claimed_by` bigint unsigned DEFAULT NULL,
  `remarks` text,
  `status` varchar(20) NOT NULL DEFAULT 'approved',
  `is_reserved` tinyint(1) NOT NULL DEFAULT 0,
  `request_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_reason` text,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `released_at` timestamp NULL DEFAULT NULL,
  `released_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_claims_request_token_unique` (`request_token`),
  KEY `idx_student_claims_status` (`status`),
  KEY `idx_student_claims_claim_date` (`claim_date`),
  KEY `student_claims_student_id_foreign` (`student_id`),
  KEY `student_claims_claim_item_id_foreign` (`claim_item_id`),
  KEY `student_claims_claimed_by_foreign` (`claimed_by`),
  KEY `student_claims_approved_by_foreign` (`approved_by`),
  KEY `student_claims_released_by_foreign` (`released_by`),
  CONSTRAINT `student_claims_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_claims_claim_item_id_foreign` FOREIGN KEY (`claim_item_id`) REFERENCES `claim_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_claims_claimed_by_foreign` FOREIGN KEY (`claimed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_claims_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_claims_released_by_foreign` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 13. activity log — created_at index for latest() ordering
-- -----------------------------------------------------------------------------
CREATE TABLE `admin_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(150) NOT NULL,
  `description` text,
  `module` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin_activities_created_at` (`created_at`),
  KEY `admin_activities_user_id_foreign` (`user_id`),
  CONSTRAINT `admin_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 14. singleton settings row (id = 1); 6 dead columns removed
-- -----------------------------------------------------------------------------
CREATE TABLE `system_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(150) DEFAULT NULL,
  `school_organization` varchar(150) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- End: 15 tables
--   framework : migrations, password_reset_tokens
--   app       : users, students, student_enrollments, bottle_collections,
--               qr_codes, certificate_templates, certificate_awards,
--               achievements, student_achievements, claim_items,
--               student_claims, admin_activities, system_settings
-- Deliberately absent: teachers, admin_settings, awards, notification_settings,
-- jobs, failed_jobs, job_batches, cache, sessions (see CLEAN_DATABASE_REVIEW.md)
-- =============================================================================
