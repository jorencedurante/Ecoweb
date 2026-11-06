# CLEAN_DATABASE_SCHEMA.md — EcoCollect Clean Database Schema

**Status:** FOR REVIEW — nothing has been implemented yet. No data was moved, no table was
dropped/altered, `.env` was not touched.

## 1. Purpose & scope

A clean, normalized MySQL schema derived from a full analysis of the current implementation
(models, controllers, views, routes, seeders, imports, API endpoints, and tests). It contains
**only what the current code actually uses**, with missing foreign keys and indexes added, and
with the type/collation inconsistencies of the live database normalized.

- 15 tables (down from 18 in the working database).
- 17 dead columns removed vs. legacy schema; certificate tables further normalized 2026-10-23
  (8 more columns + 2 FKs retired — see `CERTIFICATE_SCHEMA_NORMALIZATION.md`):
  **24 FKs + 11 new `idx_*` indexes**.
- `certificate_awards` is reused as-is (per requirement) — no new certificate table.
- Schema only. No `INSERT`/data statements anywhere.

Companion deliverables:

| File | Content |
|---|---|
| `CLEAN_DATABASE.sql` | The executable MySQL DDL for this schema |
| `CLEAN_DATABASE_REVIEW.md` | Every problem found, with `file:line` evidence |
| `CLEAN_DATABASE_MIGRATION_PLAN.md` | Safe migration plan + testing plan (STEP 4/6/7) |

## 2. Evidence base

| Source | Role |
|---|---|
| `tests/Concerns/CreatesApplicationTables.php` | Closest thing to a "current code needs" schema (15 tables) |
| Live schema dump of the working DB `ecocollect_db` | `SHOW CREATE TABLE` for all 18 tables (saved at `%TEMP%\ecocollect_db_schema.sql`) |
| All `app/Http/Controllers`, `app/Models`, `app/Imports`, `database/seeders` | Column/table usage cross-reference |
| `resources/views/**` (incl. public landing views) | Read-side usage of award/certificate columns |
| `routes/web.php`, `routes/api.php`, `routes/console.php` | Endpoints, IoT routes, schedules |
| `phpunit.xml`, `tests/**` | Which schema the test suite needs |
| Read-only queries against the 5 local MySQL databases | Inventory of tables + row counts |

## 3. Database landscape (why this matters)

| Database | Tables | Role |
|---|---|---|
| `ecocollect_db` | 18, **real data** | The database the app actually uses today |
| `ecocollect_clean` | **0 (empty)** | What `.env` already points at (`DB_DATABASE=ecocollect_clean`) |
| `ecotest_db` | 23, empty | Leftover test schema (includes legacy `teachers`) |
| `ecotest_merge_db` | 22, empty | Leftover merge experiment |
| `ecotest_merge_validation` | 19, has data | Leftover merge validation run |

`.env` targets an **empty** database while the app's real data lives in `ecocollect_db`.
This is the single biggest operational problem found (see REVIEW §1) and it also gives us a
naturally safe target: the clean schema can be created in `ecocollect_clean` **without touching
any data**.

## 4. Conventions used in the clean schema

| Aspect | Decision | Reason |
|---|---|---|
| Engine / charset | `InnoDB`, `utf8mb4`, collation **`utf8mb4_unicode_ci` everywhere** | Live DB mixes `utf8mb4_general_ci` and `utf8mb4_unicode_ci`; `config/database.php` already defaults to `utf8mb4_unicode_ci` |
| Primary keys | `BIGINT UNSIGNED AUTO_INCREMENT` | Matches live + Laravel convention (`migrations` keeps `INT UNSIGNED`) |
| Timestamps | `created_at` / `updated_at TIMESTAMP NULL DEFAULT NULL` | Normalizes the mix of `current_timestamp()` / `ON UPDATE current_timestamp()` / `NULL` defaults. All writes in the code go through Eloquent or pass timestamps explicitly |
| ENUMs | Converted to `VARCHAR` | App validates all enum values in PHP (see REVIEW §6); test schema already uses `VARCHAR` |
| FK delete rules | Children of `students` → `ON DELETE CASCADE`; creator/actor columns (`*_by`, `created_by`, `uploaded_by`, `issued_by`, `teacher_id` on students) → `ON DELETE SET NULL`; `student_enrollments.teacher_id` → `CASCADE` (live behavior) | Matches live DB behavior exactly where it exists |
| Index naming | Laravel style `table_column_foreign` / `idx_*` | Live DB mixes `fk_*` and `*_foreign` styles |

## 5. Tables (15)

> Legend: **PK** primary key, **U** unique, **FK** foreign key, **IX** index.
> Columns marked ⟂ are nullable; NOT NULL noted explicitly.

### 5.1 `migrations` — framework migration history
Framework table, required by `php artisan migrate`.

- `id` INT UNSIGNED PK AI, `migration` VARCHAR(255) NOT NULL, `batch` INT NOT NULL

### 5.2 `password_reset_tokens` — framework auth
Required by Laravel's password reset (`Password::sendResetLink`).

- `email` VARCHAR(255) PK, `token` VARCHAR(255) NOT NULL, `created_at` TIMESTAMP ⟂

### 5.3 `users` — all accounts (admin / super_admin / teacher)
Roles are stored on `users` (the legacy `teachers` table was merged away).
Evidence: `AuthController.php:91` (`$role = 'teacher'`), `SetupController.php:46` (`super_admin`),
`TeacherController` lists `users` by role.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `name` | VARCHAR(255) NOT NULL | |
| `username` | VARCHAR(50) NOT NULL **U** | Required at registration (`AuthController.php:85`), login by username |
| `email` | VARCHAR(255) NOT NULL **U** | |
| `pending_email` | VARCHAR(255) ⟂ | Email-change flow (`AccountSettingsController`) |
| `email_verified_at` | TIMESTAMP ⟂ | |
| `password` | VARCHAR(255) NOT NULL | |
| `role` | VARCHAR(30) NOT NULL DEFAULT `'teacher'` | Was ENUM; values `super_admin`/`admin`/`teacher` only; default changed from `admin` → `teacher` (code always sets it explicitly anyway) |
| `position` | VARCHAR(100) ⟂ | Adviser dropdown (`teachers.blade`) |
| `status` | VARCHAR(30) NOT NULL DEFAULT `'active'` | `'active'`/`'archived'` (`TeacherController.php:158`) |
| `email_verification_code` | VARCHAR(255) ⟂ | |
| `email_change_otp` | VARCHAR(255) ⟂ | |
| `email_change_otp_expires_at` | TIMESTAMP ⟂ | |
| `email_verification_expires_at` | TIMESTAMP ⟂ | |
| `remember_token` | VARCHAR(100) ⟂ | |
| `created_at`, `updated_at` | TIMESTAMP ⟂ | |

- IX: none beyond the two UNIQUE keys (table is small; role/status filters noted as optional).
- **Dropped:** `profile_photo` (only ever appears in `User.php:16` fillable + test schema; no write/read anywhere).

### 5.4 `students` — student registry (SF1 data + IoT point balance)
Kept **entirely as-is** except ENUM→VARCHAR normalization. All columns are read or written:
`full_name` (queried/order-by: `ClaimController` CONCAT search, report sorting; import writes it),
`qr_code` (search + display, import writes `QR-{lrn}`), `age` (import-computed),
`mother_tongue` (`student-info.blade.php:164`, print JS, import), `total_points` (IoT balance),
`teacher_id` (adviser relation on overview), etc.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `student_id` | VARCHAR(50) ⟂ **U** | Human ID (`STU…`), written by import/creation |
| `lrn` | VARCHAR(50) NOT NULL **U** | Used by IoT (`TRIM(lrn)` lookup) + all scans |
| `first_name` … `remarks` | see list below | |
| `full_name` | VARCHAR(255) ⟂ | Denormalized — **kept** (queried by LIKE/sort) |
| `grade_level` | VARCHAR(50) NOT NULL | Validated `required|in:Kindergarten…Grade 6` (`StudentController.php:69`) |
| `gender` | VARCHAR(20) NOT NULL | Was ENUM; validated `in:Male,Female` (`StudentController.php:70`) |
| `birth_date` | DATE ⟂ | |
| `age` | INT ⟂ | Import-computed (`StudentsImport.php:280`) |
| `mother_tongue`, `ip_ethnic_group`, `religion`, `nationality` | VARCHAR(100) ⟂ | SF1 details |
| `house_street` | VARCHAR(255) ⟂ | |
| `barangay`, `municipality_city`, `province`, `region` | VARCHAR(100) ⟂ | |
| `father_name`, `mother_maiden_name`, `guardian_name`, `guardian_relationship` | VARCHAR(100) ⟂ | |
| `contact_number` | VARCHAR(50) ⟂ | |
| `learning_modality` | VARCHAR(100) ⟂ | |
| `remarks` | TEXT ⟂ | Validated in student form (`StudentController.php:89`) |
| `qr_code` | VARCHAR(255) ⟂ **U** | |
| `total_points` | INT NOT NULL DEFAULT 0 | **IoT balance** — incremented by `/api/bottle-scan` |
| `status` | VARCHAR(30) NOT NULL DEFAULT `'Active'` | Was ENUM `'Active'/'Archived'`; code reads lowercase `'archived'` defensively (`StudentController.php:712`) |
| `teacher_id` | BIGINT UNSIGNED ⟂ FK→`users` SET NULL | **Kept** — legacy/adviser shortcut (`Student::teacher()`, overview `teacher_name`) |
| `created_at`, `updated_at` | TIMESTAMP ⟂ | |

- **U:** `lrn`, `student_id`, `qr_code` (all allow multiple NULLs in MySQL).
- **FK:** `students_teacher_id_foreign` (`teacher_id` → `users.id` ON DELETE SET NULL).
- **IX (new):** `idx_students_status` (`status`), `idx_students_grade_level` (`grade_level`),
  `idx_students_full_name` (`full_name`) — used by dashboards, report filters and sort-by-name.

### 5.5 `student_enrollments` — authoritative teacher↔student assignment
Evidence: `StudentController.php:137,274` (creation writes `teacher_id`, `imported_by`,
`imported_from_file`), `StudentsImport.php:433`, teacher dashboards (`whereHas('enrollments')`).

- `id` PK AI
- `student_id` BIGINT UNSIGNED NOT NULL **FK→`students` CASCADE**
- `teacher_id` BIGINT UNSIGNED NOT NULL **FK→`users` CASCADE** (live behavior)
- `grade_level` VARCHAR(50) ⟂, `section` VARCHAR(100) ⟂, `school_year` VARCHAR(20) ⟂
- `imported_by` BIGINT UNSIGNED ⟂ **FK→`users` SET NULL** (used: `StudentsImport.php:433`)
- `imported_from_file` VARCHAR(255) ⟂ (used: `StudentsImport.php:434`)
- `status` VARCHAR(30) NOT NULL DEFAULT `'active'` *(was VARCHAR(255))*
- `created_at`, `updated_at` TIMESTAMP ⟂
- **U:** `unique_enrollment` (`student_id`,`teacher_id`,`grade_level`,`section`,`school_year`)
  — kept from live (note: NULLable members weaken uniqueness; documented in REVIEW §6)

### 5.6 `bottle_collections` — collection history (IoT + manual entries)
Writes: `BottleScanController` (device POST), `BottleCollectionController` (manual).
Reads: dashboard charts, reports (`DAYNAME(collection_date)`), history lists (search by
`lrn` LIKE: `BottleCollectionController.php:87,93`).

- `id` PK AI
- `student_id` BIGINT UNSIGNED NOT NULL **FK→`students` CASCADE**
- `lrn` VARCHAR(50) NOT NULL — **denormalized, kept** (displayed + searched)
- `collection_date` DATE NOT NULL
- `collection_time` TIME ⟂ — **kept** (sort/search: `BottleCollectionController.php:93`)
- `bottle_count` INT NOT NULL DEFAULT 0, `points_earned` INT NOT NULL DEFAULT 0
- `created_by` BIGINT UNSIGNED ⟂ **FK→`users` SET NULL**
- `created_at`, `updated_at` TIMESTAMP ⟂
- **IX (new):** `idx_bottle_collections_student_date` (`student_id`,`collection_date`) — doubles
  as the index for the `student_id` FK; `idx_bottle_collections_collection_date` (`collection_date`)
  — backs the dashboard/report date queries.

### 5.7 `qr_codes` — QR registry
Writes: `QrCodeController.php:114` (+ `StudentsImport` QR generation), reads: QR pages/print.

- `id` PK AI
- `student_id` BIGINT UNSIGNED ⟂ **FK→`students` CASCADE**
- `student_name` VARCHAR(150) ⟂ (written `QrCodeController.php:114`, read in QR view)
- `qr_type` VARCHAR(50) NOT NULL DEFAULT `'LRN'`
- `qr_value` VARCHAR(255) NOT NULL **U** — content `"LRN: …\nName: …"`
- `qr_image_path` VARCHAR(255) ⟂
- `created_by` BIGINT UNSIGNED ⟂ **FK→`users` SET NULL**
- timestamps

### 5.8 `certificate_templates` — template library (normalized 2026-10-23)
Evidence: `CertificateController::storeTemplate/templatesList` flows,
`visibility`/`template_type` written by upload, `file_type` default `'docx'`.
The redundant `created_by` column (always a duplicate of `uploaded_by`) and its FK were
removed by the approved certificate normalization — see `CERTIFICATE_SCHEMA_NORMALIZATION.md`.

- `id` PK AI
- `template_name` VARCHAR(150) NOT NULL, `description` TEXT ⟂
- `file_path` VARCHAR(255) NOT NULL, `file_type` VARCHAR(50) NOT NULL DEFAULT `'docx'`
- `status` VARCHAR(50) NOT NULL DEFAULT `'active'`, `visibility` VARCHAR(50) NOT NULL DEFAULT `'private'`
- `template_type` VARCHAR(50) NOT NULL DEFAULT `'uploaded'`
- `uploaded_by` BIGINT UNSIGNED ⟂ **FK→`users` SET NULL** (canonical uploader)
- timestamps

### 5.9 `certificate_awards` — ONE certificate/award table (reused + normalized 2026-10-23)
Canonical columns: **`pdf_file_path`** (completed file; `download()` prefers it, history falls
back `pdf_file_path` → `generated_file_path` → `template_file ?? certificate_file`) and
**`issued_by`** (issuing user FK). The award feature writes: `student_id`, `teacher_id`,
`award_title`, `award_description`, `canvas_data` (fonts + name positions), `template_file`,
`generated_file_path`, `pdf_file_path`, `awarded_date`, `issued_by`, `signed_by`, `status`.
Reads: `signed_by` (`student-awards.blade.php:38`), overview mapping
(`StudentController.php:726-738`), print/download file chains.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `student_id` | BIGINT UNSIGNED ⟂ **FK→`students` CASCADE** | Nullable (draft/legacy rows) |
| `teacher_id` | BIGINT UNSIGNED ⟂ **FK→`users` SET NULL** | Adviser/ownership scope (403 checks) |
| `award_title` | VARCHAR(150) NOT NULL | |
| `award_description` | TEXT ⟂ | |
| `certificate_file` | VARCHAR(255) ⟂ | Legacy public upload flow (`store()`) + fallback |
| `template_file` | VARCHAR(255) ⟂ | Canvas background copy (editor/award) |
| `canvas_data` | LONGTEXT ⟂ | Canvas JSON: fonts, positions, show/hide choices |
| `awarded_date` | DATE NOT NULL | |
| `generated_file_path` | VARCHAR(255) ⟂ | Rendered certificate on private disk |
| `pdf_file_path` | VARCHAR(255) ⟂ | **Canonical completed file** (award flow, preferred by `download()`) |
| `status` | VARCHAR(50) NOT NULL DEFAULT `'Active'` | `'Active'`/`'Archived'` |
| `issued_by` | BIGINT UNSIGNED ⟂ **FK→`users` SET NULL** | **Canonical issuing user** |
| `signed_by` | VARCHAR(255) ⟂ | Adviser signatory name (snapshot) |
| `created_at`, `updated_at` | TIMESTAMP ⟂ | |

- **IX:** `idx_certificate_awards_awarded_date` (`awarded_date`),
  `idx_certificate_awards_status` (`status`).
- **Removed vs legacy schema (17):** `school_principal_name`, `program_coordinator_name`,
  `signatory_position`, 7× `show_*` (legacy audit), plus the 2026-10-23 normalization:
  `template_id` (dead FK, 100% NULL), `awarded_by` (text dup of `issued_by`, never read),
  `template_file_path` (never written), `certificate_title`, `certificate_type`,
  `school_year`, `remarks` (write-never slots) — full audit in
  `CERTIFICATE_SCHEMA_NORMALIZATION.md`.

### 5.10 `achievements` — quest definitions (global or per-student)
Evidence: `AchievementController.php:36-45` (create with `created_by`),
`StudentController.php:454-469` (edit writes `current_milestone`/`next_milestone`/`progress_value`),
`BottleCollectionController.php:204`, `StudentController.php:574-587` (completion checks).

- `id` PK AI
- `student_id` BIGINT UNSIGNED ⟂ **FK→`students` CASCADE** (NULL = global quest)
- `title` VARCHAR(150) NOT NULL, `description` TEXT ⟂
- `badge_name` VARCHAR(150) ⟂, `milestone` VARCHAR(150) ⟂
- `current_milestone` INT DEFAULT 0, `next_milestone` INT DEFAULT 50, `progress_value` INT DEFAULT 0
- `status` VARCHAR(50) DEFAULT `'In Progress'`
- `required_bottles` INT DEFAULT 0, `points_required` INT NOT NULL DEFAULT 0
- `created_by` BIGINT UNSIGNED ⟂ **FK→`users` SET NULL (NEW)** — written at
  `AchievementController.php:62` but had no FK/index before
- timestamps

### 5.11 `student_achievements` — earned quest records
- `id` PK AI
- `student_id` BIGINT UNSIGNED NOT NULL **FK→`students` CASCADE**
- `achievement_quest_id` BIGINT UNSIGNED NOT NULL **FK→`achievements` CASCADE**
- `awarded_date` DATE NOT NULL
- `awarded_by` BIGINT UNSIGNED ⟂ **FK→`users` SET NULL**
- **U:** `unique_student_quest` (`student_id`,`achievement_quest_id`)
- timestamps

### 5.12 `claim_items` — reward catalog
- `id` PK AI
- `item_name` VARCHAR(255) NOT NULL, `description` TEXT ⟂
- `points_required` INT DEFAULT 0, `quantity` INT DEFAULT 0
- `status` VARCHAR(50) DEFAULT `'Available'` — `'Available'`/`'Unavailable'` (`ClaimController.php:307,331`)
- `created_by` BIGINT UNSIGNED ⟂ **FK→`users` SET NULL (NEW)** — written at `ClaimController.php:310`
- timestamps
- **IX (new):** `idx_claim_items_status` (`status`)

### 5.13 `student_claims` — claim / reservation / release flow
Every column is used (claim create → approve → release → archive; reservation token;
denormalized `item_name` with LIKE search: `ClaimController` history filters).

- `id` PK AI
- `student_id` BIGINT UNSIGNED NOT NULL **FK→`students` CASCADE (NEW — was missing entirely)**
- `claim_item_id` BIGINT UNSIGNED ⟂ **FK→`claim_items` SET NULL (NEW)**
- `quantity` INT UNSIGNED NOT NULL DEFAULT 1
- `item_name` VARCHAR(255) NOT NULL — denormalized **kept** (searched)
- `points_deducted`/`points_before`/`points_after` INT DEFAULT 0
- `claim_date` DATE NOT NULL
- `claimed_by` BIGINT UNSIGNED ⟂ **FK→`users` SET NULL (NEW)**
- `remarks` TEXT ⟂
- `status` VARCHAR(20) NOT NULL DEFAULT `'approved'` — code writes `'Approved'`/`'Rejected'` (REVIEW §6 case issue)
- `is_reserved` TINYINT(1) NOT NULL DEFAULT 0, `request_token` VARCHAR(64) ⟂ **U**
- `approved_by` BIGINT UNSIGNED ⟂ **FK→`users` SET NULL**, `approved_at` TIMESTAMP ⟂, `rejected_reason` TEXT ⟂
- `is_archived` TINYINT(1) NOT NULL DEFAULT 0
- `released_at` TIMESTAMP ⟂, `released_by` BIGINT UNSIGNED ⟂ **FK→`users` SET NULL (NEW)**
- timestamps
- **IX (new):** `idx_student_claims_status` (`status`), `idx_student_claims_claim_date` (`claim_date`)
  (+ index on `student_id` provided by the new FK)

### 5.14 `admin_activities` — activity log (291 rows live)
- `id` PK AI, `user_id` ⟂ **FK→`users` SET NULL**, `action` VARCHAR(150) NOT NULL,
  `description` TEXT ⟂, `module` VARCHAR(100) ⟂, timestamps
- **IX (new):** `idx_admin_activities_created_at` (`created_at`) — backs `latest()` ordering.

### 5.15 `system_settings` — singleton settings row (`id = 1`)
`SettingsController::show/save` does `SystemSetting::firstOrCreate(['id' => 1])` with exactly
`admin_name`, `school_organization`, `address` (the only three the view renders).

- `id` BIGINT UNSIGNED PK AI
- `admin_name` VARCHAR(150) ⟂, `school_organization` VARCHAR(150) ⟂, `address` TEXT ⟂
- timestamps
- **Dropped (6):** `school_name`, `organization_name`, `system_logo`, `email_notifications`,
  `bottle_collection_reports`, `system_alerts` — never rendered or written (zero view reads; not
  even in the model's fillable except `system_logo`, which nothing sets).

## 6. Relationship map

```
users ─┬─< students.teacher_id                 (SET NULL)
       ├─< student_enrollments.teacher_id      (CASCADE)        students ─┬─< student_enrollments   (CASCADE)
       ├─< student_enrollments.imported_by     (SET NULL)                  ├─< bottle_collections    (CASCADE)
       ├─< bottle_collections.created_by       (SET NULL)                  ├─< qr_codes              (CASCADE)
       ├─< qr_codes.created_by                 (SET NULL)                  ├─< certificate_awards    (CASCADE)
       ├─< certificate_templates.uploaded_by      (SET NULL)       ├─< achievements          (CASCADE)
       ├─< certificate_awards.teacher_id / issued_by (SET NULL)            ├─< student_achievements  (CASCADE)
       ├─< achievements.created_by             (SET NULL)                  └─< student_claims        (CASCADE)
       ├─< student_achievements.awarded_by     (SET NULL)
       ├─< claim_items.created_by              (SET NULL)
       ├─< student_claims.claimed_by / approved_by / released_by (SET NULL)
       └─< admin_activities.user_id            (SET NULL)

certificate_templates (no outgoing award FK — `certificate_awards.template_id` retired 2026-10-23)
achievements           ─< student_achievements.achievement_quest_id (CASCADE)
claim_items            ─< student_claims.claim_item_id   (SET NULL)
system_settings: singleton (id = 1)
password_reset_tokens: PK = email (framework)
```

## 7. Dropped — summary (details in CLEAN_DATABASE_REVIEW.md)

**Tables (5):** `teachers` (legacy, merged into users; code never queries it),
`admin_settings` (zero references anywhere), `awards` (zero references — replaced by
`certificate_awards`), `notification_settings` (model + `User::notificationSettings()` never
consumed), plus no `generated_reports` table (the `GeneratedReport` model is dead code).

**Columns (17):** `users.profile_photo`; 6× `system_settings.*`; 10× `certificate_awards.*`
(`school_principal_name`, `program_coordinator_name`, `signatory_position`, 7× `show_*`).

**Framework tables intentionally NOT created:** `jobs`, `failed_jobs`, `job_batches`, `cache`,
`sessions` — zero `dispatch`/`ShouldQueue` in the codebase and `.env` uses file session/cache
drivers (`QUEUE_CONNECTION=database` is set but never exercised; see REVIEW §10).

## 8. Type normalizations vs. live schema

| Change | From (live) | To (clean) |
|---|---|---|
| `users.role` | ENUM('super_admin','admin','teacher') DEFAULT 'admin' | VARCHAR(30) DEFAULT 'teacher' |
| `users.status` | VARCHAR(50) | VARCHAR(30) |
| `students.gender` | ENUM('Male','Female') | VARCHAR(20) |
| `students.status` | ENUM('Active','Archived') | VARCHAR(30) DEFAULT 'Active' |
| `student_enrollments.status` | VARCHAR(255) | VARCHAR(30) |
| Collation | mixed `utf8mb4_general_ci` / `utf8mb4_unicode_ci` | `utf8mb4_unicode_ci` everywhere |
| Timestamps | mix of `current_timestamp()` (+`ON UPDATE`) and `NULL` | `TIMESTAMP NULL DEFAULT NULL` everywhere |

Every converted value is validated in PHP before insert (`in:` rules / explicit writes), so no
ENUM behavior is lost.
