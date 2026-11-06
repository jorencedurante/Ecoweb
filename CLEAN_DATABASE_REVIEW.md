# CLEAN_DATABASE_REVIEW.md — Problems found in the current database

**Method:** full read-only analysis of the codebase (models, controllers, views, routes,
seeders, imports, tests) cross-referenced against live `SHOW CREATE TABLE` output for all 18
tables of the working database `ecocollect_db`, plus inventory of all 5 local databases.
**No table was altered, no data changed, `.env` untouched.** Evidence is `file:line`.

---

## 1. Critical: `.env` points at an empty database

- `.env`: `DB_DATABASE=ecocollect_clean` → that schema has **0 tables**.
- Real data lives in `ecocollect_db` (18 tables; 62 students, 81 enrollments, 62 QR codes,
  291 activity rows, 35 collections, 5 certificates, 4 users…).
- Local database inventory: `ecocollect_db` (18, real), `ecocollect_clean` (**0**),
  `ecotest_db` (23, empty), `ecotest_merge_db` (22, empty), `ecotest_merge_validation` (19, data).
- **Impact:** a fresh `php artisan serve` with current `.env` fails on the first query
  (`users` table missing), while multiple leftover test databases drift from the app.
- **Fix:** documented in MIGRATION_PLAN Phase 1–2 (target the empty `ecocollect_clean`;
  never touch `ecocollect_db` until a verified cutover).

## 2. Critical: no `CREATE TABLE` migrations for the core tables

36 migration files exist (`database/migrations/`), but only create `achievements`,
`claim_items`, `student_claims`, `student_achievements`, `student_enrollments`,
`password_reset_tokens`. **There is no migration that creates `users`, `students`,
`qr_codes`, `bottle_collections`, `certificate_templates`, `certificate_awards`,
`admin_activities`, `system_settings`.** The test schema says it outright:
`tests/Concerns/CreatesApplicationTables.php` — *"original tables came from a SQL dump"*.

- **Impact:** `composer setup` → `php artisan migrate --force` (`composer.json`) on an empty
  DB produces a **broken partial schema** (and `Schema::table('users', …)` style migrations
  then fail on the missing table). The database is not reproducible from code.
- **Fix:** a single baseline migration created from `CLEAN_DATABASE.sql` (MIGRATION_PLAN Phase 3).

## 3. Dead tables (5) — zero references in the entire codebase

| Table | Live rows | Evidence |
|---|---|---|
| `teachers` | n/a (not even in working DB) | App never queries it; accounts are `users` rows (`TeacherController`). Only touched by a test that proves it is ignored (`tests/Feature/TeacherManagementTest.php:25`) and 2 migrations that alter it (`2026_07_01_181735`, `2026_07_01_181950`) |
| `admin_settings` | 0 | Grep across `app/`, `resources/views/`, `database/`, `routes/`, `tests/`: **0 references**. Superseded by `system_settings` (`SettingsController`) |
| `awards` | 0 | **0 references.** The award UI/flow uses `certificate_awards` (`CertificateAward` model). All other "awards" hits are relation names/UI text |
| `notification_settings` | 2 | Model `NotificationSetting` + `User::notificationSettings()` exist but are **never read or written** anywhere else (0 view/controller hits) |
| `generated_reports` | table never existed | `app/Models/GeneratedReport.php` is dead code: no table in the live DB, no usages |

## 4. Dead columns (17) — written/fillable only, never read, or entirely unused

| Table.column | Evidence |
|---|---|
| `users.profile_photo` | Only `User.php:16` (fillable) + test schema. No form field, no read, no write |
| `system_settings.school_name`, `.organization_name`, `.system_logo`, `.email_notifications`, `.bottle_collection_reports`, `.system_alerts` | `SettingsController` only ever `firstOrCreate/updateOrCreate`s `admin_name`, `school_organization`, `address`; zero view reads (`grep school_name\|system_logo` in views: 0 hits); booleans not even in the model's fillable |
| `certificate_awards.school_principal_name`, `.program_coordinator_name`, `.signatory_position` | Only `CertificateAward.php` fillable + `2026_06_22_141030_add_overlay_fields…` migration. 0 reads in any controller/view/test |
| `certificate_awards.show_logo`, `.show_certificate_title`, `.show_student_name`, `.show_award_description`, `.show_award_date`, `.show_principal_name`, `.show_program_coordinator_name` | fillable/casts (`CertificateAward.php:19-32`) + old overlay migration only. Show/hide decisions are stored inside `canvas_data` JSON by the current editor |

**Deliberately KEPT denormalizations** (they are queried — do not "clean" these later):

| Column | Why kept | Evidence |
|---|---|---|
| `students.full_name` | LIKE search + `ORDER BY full_name` (accessor overrides plain reads) | `ClaimController` CONCAT search, `ReportController.php:91,439` sorting, import writes `StudentsImport.php:254` |
| `students.teacher_id` | Adviser shortcut read next to the authoritative enrollments | `Student::teacher()`, overview `teacher_name` |
| `students.qr_code` | Displayed + generated `QR-{lrn}` | `StudentsImport.php:257,771`, QR views |
| `students.age` | Import-computed, shown in records | `StudentsImport.php:280` |
| `bottle_collections.lrn` | Displayed + searched | `BottleCollectionController.php:38,87,93` |
| `bottle_collections.collection_time` | Sort/search in history | `BottleCollectionController.php:93` |
| `student_claims.item_name` | Claim history search | `ClaimController` history filters |
| `certificate_awards.certificate_title`, `.remarks`, `.signed_by` | Public/award views read them | `student-awards.blade.php:29,38,49`, `landing-student-details.blade.php:153` |
| `student_enrollments.imported_by`, `.imported_from_file` | Audit of imports | `StudentController.php:137,274`, `StudentsImport.php:433-434` |

## 5. Missing foreign keys (6 added in clean schema) — orphan risk

Present in neither the live DB nor the test schema; all are written by app code:

| Column | Written at | Clean schema adds |
|---|---|---|
| `student_claims.student_id` (**no FK and no index at all**) | every claim (`ClaimController`) | FK → `students` CASCADE |
| `student_claims.claim_item_id` | `ClaimController` claim creation | FK → `claim_items` SET NULL |
| `student_claims.claimed_by` | `ClaimController` | FK → `users` SET NULL |
| `student_claims.released_by` | release flow | FK → `users` SET NULL |
| `achievements.created_by` | `AchievementController.php:62` | FK → `users` SET NULL |
| `claim_items.created_by` | `ClaimController.php:310` | FK → `users` SET NULL |

(`student_claims.approved_by` already had an FK; the others above had none.)

## 6. Missing indexes (11 `idx_*` added in clean schema)

| Index | Backs |
|---|---|
| `bottle_collections.collection_date` | dashboard/report date scans (`DashboardController.php:50`, `ReportController.php:235`) |
| `bottle_collections (student_id, collection_date)` | per-student history + covers the FK |
| `students.status`, `students.grade_level`, `students.full_name` | dashboard counts, report filters, name sorting |
| `student_claims.status`, `student_claims.claim_date` | claim board filters + history ordering |
| `claim_items.status` | catalog filters |
| `certificate_awards.awarded_date`, `.status` | award lists (`orderByDesc('awarded_date')`) + archive filter |
| `admin_activities.created_at` | `latest()` activity feed |

## 7. Type / value inconsistencies

| Issue | Detail |
|---|---|
| ENUM vs VARCHAR drift | live: `users.role` ENUM, `students.gender` ENUM, `students.status` ENUM; test schema: all plain VARCHAR — the two schemas already disagree |
| `users.role` default `'admin'` | live DB default is `admin` while registration explicitly picks `teacher` (`AuthController.php:91`); a raw insert without `role` silently becomes an admin. Clean default: `'teacher'` |
| Status letter-case split | `student_claims.status` default `'approved'` (lowercase, live schema) but code writes `'Approved'`/`'Rejected'` (`ClaimController.php:405,454,506,592`) → mixed-case data in one column; student status `'Active'` vs reads of `'archived'` (`StudentController.php:712` — defensively lowercased) |
| Collation mix | `migrations` + `student_enrollments` are `utf8mb4_unicode_ci`; the other 16 tables `utf8mb4_general_ci`; `config/database.php` default is `utf8mb4_unicode_ci` |
| Timestamp style mix | some tables `DEFAULT current_timestamp() ON UPDATE …`, others `NULL DEFAULT NULL` |
| `unique_enrollment` weakened by NULLs | MySQL unique keys allow repeated rows when any member is NULL — `grade_level`/`section`/`school_year` are nullable, so duplicates are possible (kept as-is for behavior parity; documented) |

## 8. Model ↔ schema drift

- `Achievement::$fillable` contains **`achieved_date`, a column that does not exist** in the
  live DB, the test schema, or the clean schema. Harmless today (nothing passes it) but it
  will throw if ever mass-assigned.
- `CertificateAward` fillable/casts still list the 10 dropped columns — safe (nothing passes
  them) but should be trimmed together with the column removal (optional code cleanup).
- `GeneratedReport.php` and `NotificationSetting.php` are dead models; `User::$fillable`
  contains dead `profile_photo`; `User::notificationSettings()` relation is dead.
- `tests/Concerns/CreatesApplicationTables.php` creates `teachers` + `notification_settings`
  — only matters if the suite is ever pointed at MySQL (see MIGRATION_PLAN §Testing T1/T3).

## 9. Raw SQL / portability notes

Only 4 raw-SQL touchpoints exist (all MySQL-oriented, all fine on MySQL):

1. `BottleScanController.php:32` — `whereRaw('TRIM(lrn) = ?')` → **defeats the `lrn` index**;
   works, but an index cannot help this query. (Data-hygiene note only; no code change.)
2. `DashboardController.php:50` + `ReportController.php:235` — `DAYNAME(collection_date)`
   (MySQL-only; this is why 2 tests skip outside MySQL).
3. `ClaimController` — `CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?` ×5
   (MySQL-compatible; the reason `students.full_name` must stay).

## 10. Queue/cache/session mismatch (configuration)

- `.env`: `QUEUE_CONNECTION=database`, `SESSION_DRIVER=file`, `CACHE_STORE=file`.
- Code: **zero** `dispatch(` / `ShouldQueue` / `Bus::` anywhere → `jobs`/`failed_jobs`/
  `job_batches` are never written; `cache`/`sessions` tables are not needed (file drivers).
- Clean schema therefore omits them (task: only tables required by current implementation).
- **Risk:** the first future `dispatch()` against `QUEUE_CONNECTION=database` would fail.
  Choose one in Phase 3: (a) keep omitting + switch to `QUEUE_CONNECTION=sync`, or
  (b) add the 3 queue tables for forward compatibility. Recommendation: (a).

## 11. Migration-chain hazards (why "just run migrate" is unsafe)

Guarded (`hasTable`/`hasColumn`) — become no-ops once the baseline exists: most
`add_*` migrations, `create_achievements/claim_items/student_claims/student_achievements/
password_reset_tokens`, `ensure_users…`, `merge_legacy_teachers…` (skips without `teachers`).

**Unguarded — would ERROR on a clean database:**

| Migration | Failure |
|---|---|
| `2026_07_06_161936_create_student_enrollments_table.php` | no `hasTable` guard → "table already exists" |
| `2026_07_01_181735_add_user_id_to_teachers_table.php` | `Schema::table('teachers', …)` with no table guard → fails (no `teachers` in clean schema) |
| `2026_07_01_181950_add_user_id_to_teachers_table.php` | same |
| `2026_10_18_000001_ensure_users_table_supports_all_accounts.php:36` | would **re-add** the dropped `users.profile_photo` column |
| `2026_07_06_162525_backfill_student_enrollments.php` | runs a data backfill (harmless on empty data, noisy on migrated data) |

→ Handled by the plan: baseline + explicit skip list (MIGRATION_PLAN Phase 3).
