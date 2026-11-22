# EcoCollect Database Usage Audit

**Date:** 2026-11-10  
**Environment:** Local configured DB `ecocollect_db` (`.env`), target baseline `ecocollect_production` (15 tables by design, **not inspected/modified**)  
**Scope:** Full codebase audit (models, controllers, services/imports/mail/middleware/helpers, routes, Blade views/JS, API, tests, migrations). No databases or records were modified.

---

## 1. The 15 Intended Production Tables

Based on active models, routes, controllers, and migrations (and treating `ecocollect_production` as the 15-table baseline per instruction):

| # | Table | Primary model | Purpose (active) |
|---|---|---|---|
| 1 | `users` | `App\Models\User` | Unified accounts: `super_admin`, `admin`, `teacher` (`role`), auth/verification/email-change, status/profile. |
| 2 | `students` | `App\Models\Student` | Student profiles/SF1 fields, points, status/archive metadata, legacy `teacher_id`. |
| 3 | `student_enrollments` | `App\Models\StudentEnrollment` | Teacher–student enrollment per grade/section/school year; **authoritative assignment/scoping**. |
| 4 | `bottle_collections` | `App\Models\BottleCollection` | Bottle collection records, points earned, recorder, optional `enrollment_id`. |
| 5 | `qr_codes` | `App\Models\QrCode` | Student QR codes/values/images. |
| 6 | `achievements` | `App\Models\Achievement` | Global achievement quest definitions (teacher-owned via `created_by`, scoped to assigned students). |
| 7 | `student_achievements` | `App\Models\StudentAchievement` | Earned badges/quest awards per student (unique per student+quest), optional `enrollment_id`. |
| 8 | `certificate_templates` | `App\Models\CertificateTemplate` | Certificate templates (visibility/type/uploaded_by). |
| 9 | `certificate_awards` | `App\Models\CertificateAward` | Issued/designed/awarded certificates, files/canvas/signatories (`signed_by`, `principal_name`). |
| 10 | `claim_items` | `App\Models\ClaimItem` | Redeemable items inventory (points/qty/status). |
| 11 | `student_claims` | `App\Models\StudentClaim` | Claim requests/history (pending/approved/rejected/released-archived), reservations, points snapshots, optional `enrollment_id`. |
| 12 | `admin_activities` | `App\Models\AdminActivity` | Audit/activity log (`user_id`, action, module, description). |
| 13 | `system_settings` | `App\Models\SystemSetting` | System/school configuration. |
| 14 | `notification_settings` | `App\Models\NotificationSetting` | Per-user notification preferences. |
| 15 | `password_reset_tokens` | (Laravel auth) | Password reset tokens. |

**Explicitly not part of intended 15:** `teachers` (legacy; see §6). Infra tables not modeled/required by app domain code: `cache`, `sessions`, `jobs`, `failed_jobs`, `migrations` history table is framework bookkeeping (not a domain table).

---

## 2. Each Table's Actual Usage in the Current Application

| Table | Key code usage | Columns/relations relied on (observed) |
|---|---|---|
| `users` | `AuthController`, `AccountSettingsController`, `TeacherController`, `StudentController`, `ClaimController`, `CertificateController`, `ReportController`, `DashboardController`, middleware | `id,name,username,email,password,role,position,status,profile_photo`, email verification/change OTP fields, `email_verified_at`, timestamps. Relations: students (legacy), studentEnrollments, activities, notificationSettings. |
| `students` | `StudentController`, `BottleCollectionController`, `ClaimController`, `CertificateController`, `ReportController`, `DashboardController`, `PublicController`, `QrCodeController`, `StudentsImport`, API scan | Profile/SF1 fields (`lrn, student_id, first/middle/last/full_name, gender, birth_date, age, grade_level, address/family/contact/modality/remarks, region, nationality`), `qr_code`, `total_points`, `status`, `archive_type`, `archive_school_year`, `archive_batch_id`, `teacher_id` (legacy). Relations to enrollments, collections, achievements/earned, awards/certificates, qrCodes, claims. |
| `student_enrollments` | `StudentController` (store/update/sync/archive/re-enroll/restore/school-year ops), `BottleCollectionController`, `ClaimController`, `CertificateController`, scoping in `Student` scopes/methods, `PublicController` | `student_id, teacher_id, grade_level, section, school_year, imported_by, imported_from_file, status`. **Authoritative** for teacher visibility/assignment (`status='active'`, `teacher_id`). |
| `bottle_collections` | `BottleCollectionController`, `Api\BottleScanController`, `BottleCollection` model, `Student`/reports/dashboard/public | `student_id, lrn, collection_date, collection_time, bottle_count, points_earned, created_by, enrollment_id`. 1 bottle = 1 point logic in controllers. |
| `qr_codes` | `QrCodeController`, `StudentsImport`, `StudentController` views/relations | `student_id, student_name, qr_type, qr_value, qr_image_path, created_by`. |
| `achievements` | `AchievementController`, `StudentController`, `PublicController`, `Student::assignedActiveQuests/syncEarnedAchievements/achievementProgressData` | Global quests: `student_id` nullable, `title, description, badge_name, milestone, required_bottles, points_required, status, created_by`, progress fields. Scoped to student's assigned teacher(s). |
| `student_achievements` | `StudentAchievement` model, `StudentController` (overview/achievements/historical), `PublicController`, `ReportController`, `Student` relations/sync | `student_id, achievement_quest_id, awarded_date, awarded_by, enrollment_id`. Unique (`student_id`,`achievement_quest_id`) per model/migrations intent. |
| `certificate_templates` | `CertificateController` (store/list/use), `CertificateTemplate` model | `template_name, description, file_path, file_type, status, visibility, template_type, uploaded_by`. |
| `certificate_awards` | `CertificateController` (index/store/saveCanvas/award/download/print/file/public), `StudentController`, `ReportController` | `student_id (nullable), teacher_id, issued_by, award_title, award_description, certificate_file, template_file, canvas_data, awarded_date, generated_file_path, pdf_file_path, status, signed_by, principal_name`. |
| `claim_items` | `ClaimController`, `PublicController` (items/points/request) | `item_name, description, points_required, quantity, status, created_by`. Defensive `Schema::hasColumn` checks for `item_name/name/description`. |
| `student_claims` | `ClaimController` (index/storeItem/claimItem/approve/reject/archive/filterPending/History), `PublicController`, `ReportController`, `Student` relations | `student_id, claim_item_id, quantity, item_name, points_deducted, points_before/after, claim_date, claimed_by, remarks, status, approved_by, approved_at, rejected_reason, is_archived, released_at, released_by, is_reserved, request_token, enrollment_id`. |
| `admin_activities` | `AdminActivity` model; logged from many controllers (students, claims, certificates, bottle collections, achievements, teachers/settings as applicable) | `user_id, action, description, module`, `UPDATED_AT` disabled. |
| `system_settings` | `SettingsController`, `SystemSetting` model | Admin/school/system config fields (per model). |
| `notification_settings` | `NotificationSetting` model, `User::notificationSettings()` | `user_id` + preference flags. |
| `password_reset_tokens` | Laravel password reset flow (`AuthController` routes/views) | Standard Laravel usage. |

---

## 3. Tables Used by Admin Features (`/admin/*`, `admin.or.super`)

| Area | Tables |
|---|---|
| Dashboard/Reports/Admin activities | `users`, `students`, `student_enrollments`, `bottle_collections`, `student_claims`, `claim_items`, `student_achievements`, `achievements`, `certificate_awards`, `admin_activities`, `qr_codes` (as applicable) |
| Student management (view/manage per role rules) | `students`, `student_enrollments`, `users`, `admin_activities`, `bottle_collections`, `qr_codes`, `student_achievements`, `student_claims`, `certificate_awards` |
| Bottle collection | `bottle_collections`, `students`, `student_enrollments`, `users`, `admin_activities`, `student_achievements`, `achievements` |
| Certificates/Achievement quests | `certificate_awards`, `certificate_templates`, `students`, `student_enrollments`, `achievements`, `users`, `admin_activities` |
| Claims/Items (admin-only gated) | `claim_items`, `student_claims`, `students`, `student_enrollments`, `users`, `admin_activities` |
| QR codes | `qr_codes`, `students`, `student_enrollments`, `users` |
| Accounts/Teachers (admin-only gated) | `users` (role teacher/admin/super_admin management) |
| Settings/System settings | `users`, `system_settings`, `notification_settings`, `password_reset_tokens` (auth-related) |

---

## 4. Tables Used by Teacher Features (`/teacher/*`, `teacher.only`)

| Area | Tables |
|---|---|
| Dashboard/Reports/Activities | `users`, `students`, `student_enrollments`, `bottle_collections`, `student_claims`, `claim_items`, `student_achievements`, `achievements`, `certificate_awards`, `admin_activities` (scoped to teacher's students/enrollments where applicable) |
| Student management (own/assigned) | `students`, `student_enrollments`, `users`, `bottle_collections`, `qr_codes`, `student_achievements`, `student_claims`, `certificate_awards`, `admin_activities` |
| Bottle collection | `bottle_collections`, `students`, `student_enrollments`, `users`, `student_achievements`, `achievements` |
| Certificates/Achievement quests (teacher-owned quests) | `achievements` (`created_by` ownership enforced), `certificate_templates`, `certificate_awards`, `students`, `student_enrollments`, `users` |
| QR codes | `qr_codes`, `students`, `student_enrollments` |
| Account settings | `users`, `password_reset_tokens` |

**Note:** Claims management routes are under admin portal/middleware in current routing (`admin.claims.*`); teacher portal does not expose claim CRUD routes, but shared data/scoping logic exists in controllers.

---

## 5. Tables Used by Key Features

| Feature | Tables | Key behavior observed |
|---|---|---|
| **Student enrollment** | `students`, `student_enrollments`, `users`, `admin_activities` | `StudentController::syncEnrollment/store/update/reEnroll`; enrollment rows drive assignment/visibility. |
| **Archiving** | `students`, `student_enrollments`, `users`, `admin_activities` | Individual/school-year archive via `students.status`, `archive_type`, `archive_school_year`, `archive_batch_id`; bulk/unarchive/move operations scoped by enrollments/ownership. |
| **QR scanning** | `qr_codes`, `students`, `bottle_collections`, `users` (auth context varies) | `Api\BottleScanController` queries `students` (`DB::table`) and records collections; `QrCodeController` manages QR records/images. |
| **Bottle collection** | `bottle_collections`, `students`, `student_enrollments`, `achievements`, `student_achievements`, `users`, `admin_activities` | Records collections linked to active enrollment, increments `students.total_points`, triggers auto-award sync (`syncEarnedAchievements`) per existing rules. |
| **Achievements/quests** | `achievements`, `student_achievements`, `students`, `student_enrollments`, `bottle_collections`, `users` | Quests are teacher-owned global definitions; eligibility based on bottles/points; awards created idempotently (`firstOrCreate`) and now associated with active enrollment on creation in current code. |
| **Certificates** | `certificate_awards`, `certificate_templates`, `students`, `student_enrollments`, `users`, `admin_activities` | Template upload/list, award/saveCanvas flows, generated/PDF/image files, signed URLs for public view/download; `principal_name` now required by current code/schema fix. |
| **Claims** | `claim_items`, `student_claims`, `students`, `student_enrollments`, `users`, `admin_activities` | Public/item requests, pending/approve/reject/archive/release, point reservations (`is_reserved`, `request_token`), stock/points snapshots, enrollment linkage. |

---

## 6. Legacy Tables Not Used by the Current Application

| Table | Evidence | Assessment |
|---|---|---|
| `teachers` | **Migrations:** `2026_07_01_181735/181950_add_user_id_to_teachers_table.php`, `2026_10_18_000002_merge_legacy_teachers_into_users.php` (merges/links into `users`, keeps table). **Tests:** `tests/Concerns/CreatesApplicationTables.php` creates `teachers` if missing; `tests/Feature/TeacherManagementTest.php` inserts/queries `DB::table('teachers')`; `tests/Feature/EmailVerificationTest.php` asserts missing in `teachers`. **Runtime app code:** no `App\Models\Teacher`; `TeacherController` and routes manage `users` with teacher/admin roles (`admin.teachers*` operate on `User` records). No controllers/services/models use `teachers` table in runtime paths. | **Legacy/obsolete for runtime.** Current application uses unified `users` + `student_enrollments`. Do **not** restore/add to `ecocollect_production`. Test harness references are compatibility artifacts, not production requirements. |
| (none other found) | Searches across `app/`, `routes/`, `resources/` show no active domain usage of `cache`, `sessions`, `jobs`, `failed_jobs`, `personal_access_tokens` as application tables. | Not part of 15-table domain baseline. |

**Caution:** Absence of direct string/table references is not sole proof; checked models, controllers, routes, imports, middleware, API, and dynamic `DB::table` usage patterns. No indirect runtime dependency on `teachers` found.

---

## 7. Pending Migrations: Classification

**Local status (`php artisan migrate:status`):** all 40 migrations listed are **Ran** in this environment (batches 1–30). "Pending" in production/other DBs must be evaluated against the intended 15-table schema.

| Migration | Classification | Rationale / schema effect |
|---|---|---|
| `2026_01_01_000001_add_remember_token_to_users` | **Required if missing** (likely already represented) | Adds `users.remember_token`. |
| `2026_01_01_000002_create_achievements_table` | **Required** | Creates `achievements` (core table 6). |
| `2026_06_11_000001_create_password_reset_tokens_table` | **Required** | Creates `password_reset_tokens` (core table 15). |
| `2026_06_11_000002_add_username_to_users_table` | **Required if missing** | Adds `users.username`. |
| `2026_06_11_140620_add_student_name_to_qr_codes_table` | **Required if missing** | Adds `qr_codes.student_name` (used by QR flows). |
| `2026_06_11_141030_add_certificate_fields_to_certificate_awards_table` | **Already represented / partially obsolete** | Adds legacy certificate fields; later normalized/replaced (`principal_name`, `signed_by`, etc.). |
| `2026_06_11_143946_add_achievement_progress_columns_to_achievements_table` | **Required** | Adds progress/status fields used by `Achievement` model/UI. |
| `2026_06_11_150555_add_achievement_quest_columns_to_achievements_table` | **Required** | Adds `required_bottles`, `created_by`, quest fields central to scoping/awarding. |
| `2026_06_11_152653_create_claim_items_table` | **Required** | Creates `claim_items` (core table 10). |
| `2026_06_11_152654_create_student_claims_table` | **Required** | Creates `student_claims` (core table 11). |
| `2026_06_11_154216_create_student_achievements_table` | **Required** | Creates `student_achievements` (core table 7). |
| `2026_06_22_141030_add_overlay_fields_to_certificate_awards_table` | **Requires investigation / likely obsolete** | Overlay toggle columns; current award/canvas/file flows do not appear to depend on them in controllers/models audited. Keep only if present; do not add if unused. |
| `2026_07_01_164303_add_teacher_id_to_students_table` | **Legacy-supporting / optional** | Adds `students.teacher_id` (FK users). Not authoritative for scoping (enrollments are). |
| `2026_07_01_172246_add_status_to_student_claims_table` | **Obsolete/superseded** | Simple status add; superseded by `2026_07_01_173029`. |
| `2026_07_01_173029_add_approval_fields_to_student_claims_table` | **Required** | Adds status/approval/rejection fields and normalizes values; used by claims workflow. |
| `2026_07_01_181735_add_user_id_to_teachers_table` | **Obsolete for 15-table baseline** | Targets legacy `teachers` table (not in intended 15). |
| `2026_07_01_181950_add_user_id_to_teachers_table` | **Obsolete (no-op)** | Empty up/down. |
| `2026_07_06_075345_add_email_verification_to_users_table` | **Required** | Adds verification fields; backfills existing users verified. |
| `2026_07_06_085038_update_email_verification_code_length_on_users_table` | **Required** | Widens verification code column. |
| `2026_07_06_161932_add_sf1_details_to_students_table` | **Required** | Adds SF1/profile fields used throughout students/imports. |
| `2026_07_06_161936_create_student_enrollments_table` | **Required** | Creates `student_enrollments` (core table 3). |
| `2026_07_06_162525_backfill_student_enrollments` | **Already represented / data-only** | Backfills enrollments from existing assignments; idempotent intent. Not needed if clean schema/data already correct. |
| `2026_07_06_185336_add_new_fields_to_certificate_templates_table` | **Required** | Adds `visibility`, `template_type`, `created_by`/`uploaded_by`-related fields per implementation. |
| `2026_07_06_185341_add_new_fields_to_certificate_awards_table` | **Required/partially represented** | Adds teacher/signatory/metadata fields; some later normalized. |
| `2026_07_07_044436_add_certificate_file_to_certificate_awards_table` | **Required** | Adds `certificate_file`. |
| `2026_07_07_050000_add_canvas_data_to_certificate_awards_table` | **Required** | Adds `canvas_data` (used by canvas/award flows). |
| `2026_07_07_060000_add_template_file_to_certificate_awards` | **Required** | Adds `template_file`. |
| `2026_07_08_000001_make_student_id_nullable_in_certificate_awards` | **Required** | Allows template/standalone awards. |
| `2026_07_08_000002_add_email_change_fields_to_users_table` | **Required** | Adds pending email/OTP fields. |
| `2026_08_30_000000_add_archive_fields_to_student_claims_table` | **Required** | Adds `is_archived`, `released_at`, `released_by` (archive/release flow). |
| `2026_08_30_100000_add_quantity_to_student_claims_table` | **Required** | Adds `quantity` (core to claims). |
| `2026_10_18_000001_ensure_users_table_supports_all_accounts` | **Required (guarded)** | Ensures unified account columns/lengths. |
| `2026_10_18_000002_merge_legacy_teachers_into_users` | **Obsolete for 15-table baseline / data-only** | Merges legacy `teachers` into `users`; keeps `teachers` table. Not applicable if `teachers` excluded. |
| `2026_10_19_000001_add_reservation_fields_to_student_claims_table` | **Required** | Adds `is_reserved`, `request_token` (unique) for reservation/idempotency. |
| `2026_10_22_000001_add_region_and_nationality_to_students_table` | **Required** | Adds `region`, `nationality` (forms/import/model). |
| `2026_10_23_000100_normalize_certificate_tables` | **Requires investigation (conditional)** | Phase A backfills safe; Phases B/C destructive and **opt-in via env flags**. Do not run destructive phases on production without review. |
| `2026_11_03_170000_add_enrollment_id_to_activity_tables` | **Required** | Adds nullable `enrollment_id` (+FK) to `bottle_collections`, `student_claims`, `student_achievements`. |
| `2026_11_05_000000_add_archive_type_to_students_table` | **Required** | Adds `archive_type`, `archive_school_year`. |
| `2026_11_06_000000_add_archive_batch_id_to_students_table` | **Required** | Adds `archive_batch_id` + index. |
| `2026_11_10_012522_add_principal_name_to_certificate_awards_table` | **Required** | Adds `certificate_awards.principal_name` (fixes award save). |

**Do not mark Pending as completed without verifying actual schema effects in each target DB.**

---

## 8. Missing Columns and Other Required Changes in `ecocollect_db`

> Compared to intended 15-table/current-code usage. **No changes applied.**

| Table | Expected by current code | Check/fix in `ecocollect_db` (additive only) |
|---|---|---|
| `users` | Unified account columns incl. verification/email-change fields, `role/position/status/profile_photo`, `username` | Ensure all present and compatible lengths (see `2026_10_18_000001`, verification/email-change migrations). |
| `students` | SF1/profile fields, `region`, `nationality`, `archive_type`, `archive_school_year`, `archive_batch_id`, `total_points/status/qr_code/lrn` | Ensure columns exist; preserve existing data. `teacher_id` is legacy but present in model/fillable. |
| `student_enrollments` | `student_id, teacher_id, grade_level, section, school_year, imported_by, imported_from_file, status`, unique enrollment key | **`school_year` must be preserved as NOT NULL** (business-critical). Verify column nullability/data; if nullable in legacy schema, fix data then enforce NOT NULL (schema change) — but do not execute here. |
| `bottle_collections` | `enrollment_id` (nullable FK to `student_enrollments`) | Add if missing (`2026_11_03_170000`). |
| `student_claims` | `quantity`, approval fields, `is_archived/released_at/released_by`, `is_reserved/request_token` (unique), `enrollment_id` | Add missing columns/indexes/FKs as per migrations; ensure `quantity` default/values sane. |
| `student_achievements` | `enrollment_id` (nullable FK), uniqueness on (`student_id`,`achievement_quest_id`) | Add `enrollment_id` if missing; ensure unique constraint exists. |
| `achievements` | `required_bottles`, `points_required`, `created_by`, `status`, progress fields | Ensure quest definition columns match model usage. |
| `certificate_awards` | `principal_name`, `signed_by`, `teacher_id`, `template_file`, `canvas_data` (longText), file path columns, nullable `student_id` | Ensure present; `principal_name` required for current award save flow. |
| `certificate_templates` | `visibility`, `template_type`, uploader linkage fields | Ensure present. |
| `qr_codes` | `student_name` (and expected QR fields) | Ensure present. |
| **Relationships/indexes/FKs** | Enrollment FKs, claim/collection/achievement `enrollment_id` FKs, uniqueness (`student_enrollments` unique key, `student_achievements` unique, `student_claims.request_token` unique) | Verify and add missing constraints/indexes additively if absent. |
| **Data types** | `canvas_data` long text; OTP/token fields sufficiently long; dates/timestamps as used | Align with migrations/model casts where needed. |

---

## 9. Code References to Nonexistent Tables or Columns

| Location | Reference | Risk/notes |
|---|---|---|
| `tests/Concerns/CreatesApplicationTables.php` | Creates `teachers` table; adds FK to `users` | **Test harness only** (guarded by `hasTable`). Not required by runtime app; irrelevant to 15-table production baseline. |
| `tests/Feature/TeacherManagementTest.php` | `DB::table('teachers')->insert(...)`, assertions on `teachers` | **Test-only.** Assumes legacy `teachers` table exists. May fail or be incompatible with 15-table-only schema unless adjusted (but tests not to be assumed accurate). |
| `tests/Feature/EmailVerificationTest.php` | `assertDatabaseMissing('teachers', ...)` | **Test-only** legacy assertion. |
| `app/Http/Controllers/Api/BottleScanController.php` | `DB::table('students')` raw query | OK if `students` exists (core table). Bypasses model scopes/relations. |
| `app/Http/Controllers/ClaimController.php` | `Schema::hasColumn('claim_items', 'item_name'/'name'/'description')` checks | Defensive for schema variance; no hard dependency on nonexistent columns, but indicates historical `name` vs `item_name` differences. |
| `app/Http/Controllers/QrCodeController.php` | `Schema::hasColumn('students', 'middle_name')` | Defensive; safe. |
| `app/Http/Controllers/PageController.php` | Mock/TODO achievement methods/views not fully wired to live data in some paths | Not a DB table/column mismatch; legacy placeholder code. |
| Migrations touching `teachers` | `add_user_id_to_teachers_table`, `merge_legacy_teachers_into_users` | Reference legacy table excluded from 15-table baseline; **obsolete/skip conceptually** for production schema. |

No active runtime references found to `teachers`, `password_resets` (uses `password_reset_tokens`), or other removed domain tables in `app/`, `routes/`, `resources/`.

---

## 10. Safe Recommendations to Make Current Application Compatible with Existing Production Schema

1. **Treat 15-table schema as baseline:** Do not add/restore tables (especially `teachers`) in `ecocollect_production`; do not modify production. Align `ecocollect_db` additively to match required columns/indexes/FKs used by current code.
2. **Verify and add only missing columns/indexes/FKs (additive):** Prioritize `student_enrollments.school_year` (NOT NULL), `bottle_collections.enrollment_id`, `student_claims` reservation/approval/archive/enrollment fields, `student_achievements.enrollment_id` + uniqueness, `certificate_awards.principal_name` and file/canvas/signatory fields, `achievements` quest ownership/requirement fields, student archive/SF1 fields.
3. **Preserve `student_enrollments.school_year` as NOT NULL:** Audit/fix any NULL data first; then enforce NOT NULL constraint if needed. Do not relax to nullable.
4. **Teacher model alignment:** Ensure all teachers are `users` with `role='teacher'` and appropriate `status`; rely on `student_enrollments` for assignment/scoping. Remove runtime dependency assumptions on `teachers` table (none found in app code).
5. **Enrollment-scoped integrity:** Maintain nullable FKs from activity tables to `student_enrollments` where used; keep historical rows valid (NULL `enrollment_id` allowed).
6. **Claims/achievements/certificates correctness:** Ensure schema supports existing reservation/idempotency, auto-award idempotency, and certificate storage fields without changing business logic.
7. **Migration handling:** Review each Pending migration against target schema effects; apply only required additive changes with backups. Never `migrate:fresh/reset/db:wipe`. Do not mark as ran without verification. Treat `teachers`-related and destructive/opt-in certificate normalization phases as non-applicable or high-risk for production baseline.
8. **Testing considerations:** `CreatesApplicationTables`/`TeacherManagementTest` assume `teachers` table; do not treat all existing tests as authoritative for 15-table production. Validate with isolated test DB and targeted feature tests.
9. **HTTP 500 investigation:** Common causes are missing columns (e.g., `certificate_awards.principal_name` — now fixed in local code+schema) or enrollment/claims/achievement columns expected by controllers/models. Check logs against actual schema per environment.
10. **No business-logic/data changes:** Keep calculations, balances, reservations, archive/enrollment rules, permissions, and records intact; only schema compatibility fixes as required.

---

## Deliverables Checklist
- [x] 15 intended production tables identified
- [x] Per-table actual usage documented
- [x] Admin feature table usage
- [x] Teacher feature table usage
- [x] Enrollment/archiving/QR/bottle collection/achievements/certificates/claims table mapping
- [x] Legacy tables identified (`teachers`)
- [x] Pending migrations classified
- [x] Missing columns/required changes for `ecocollect_db` listed
- [x] Code references to nonexistent/legacy tables or risky columns noted
- [x] Safe compatibility recommendations provided
- [x] No database modifications performed
