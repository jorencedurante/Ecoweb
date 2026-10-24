# CLEAN_DATABASE_MIGRATION_PLAN.md — safe migration + testing plan

**Status:** PLAN FOR REVIEW. Nothing below has been executed. Hard safety rules are in §0.

## 0. Safety rules (from requirements — never violated)

1. **No destructive SQL** at any point: no `DROP`/`TRUNCATE`/`DELETE`/`ALTER` against existing
   data, no `php artisan migrate:fresh`, no `db:wipe`, no `db:seed` against a database with data.
2. **`.env` is not overwritten.** Where a different database must be selected, use a throwaway
   copy (`.env.clean`), command-line env overrides, or `mysql < script` directly.
3. **`ecocollect_db` (real data) is read-only** until a verified cutover; take a backup first.
4. **No production/Railway changes.** Everything runs locally.
5. **`certificate_awards` is reused** — no new certificate table, no application-code behavior
   changes required to adopt the schema.
6. Before anything: user review of `CLEAN_DATABASE_SCHEMA.md` + `CLEAN_DATABASE.sql`.

## 1. Current state recap

- Working data: `ecocollect_db` (18 tables). App `.env`: `ecocollect_clean` (**empty**).
- Deliverables: `CLEAN_DATABASE_SCHEMA.md`, `CLEAN_DATABASE.sql`, `CLEAN_DATABASE_REVIEW.md`,
  this plan. App code untouched; test suite green (123 passed / 2 skipped / 736 assertions).

## 2. Phase 0 — pre-flight (read-only)

```powershell
# 1. Backup the real DB (writes ONLY a new dump file — never touches the source)
mysqldump -u root --routines --triggers ecocollect_db > ecocollect_db_backup_%date%.sql

# 2. Record source row counts for later comparison (read-only)
mysql -u root -e "SELECT 'students', COUNT(*) FROM ecocollect_db.students UNION ALL
  SELECT 'student_enrollments', COUNT(*) FROM ecocollect_db.student_enrollments UNION ALL
  SELECT 'bottle_collections', COUNT(*) FROM ecocollect_db.bottle_collections UNION ALL
  SELECT 'certificate_awards', COUNT(*) FROM ecocollect_db.certificate_awards UNION ALL
  SELECT 'users', COUNT(*) FROM ecocollect_db.users;"

# 3. Orphan checks BEFORE any FK-enabling import (all must return 0)
mysql -u root -e "
  SELECT COUNT(*) AS orph_claims      FROM ecocollect_db.student_claims c LEFT JOIN ecocollect_db.students s ON s.id=c.student_id WHERE s.id IS NULL;
  SELECT COUNT(*) AS orph_claims_item FROM ecocollect_db.student_claims c LEFT JOIN ecocollect_db.claim_items i ON i.id=c.claim_item_id WHERE c.claim_item_id IS NOT NULL AND i.id IS NULL;
  SELECT COUNT(*) AS orph_claims_by   FROM ecocollect_db.student_claims c LEFT JOIN ecocollect_db.users u ON u.id=c.claimed_by WHERE c.claimed_by IS NOT NULL AND u.id IS NULL;
  SELECT COUNT(*) AS orph_ach_by      FROM ecocollect_db.achievements a LEFT JOIN ecocollect_db.users u ON u.id=a.created_by WHERE a.created_by IS NOT NULL AND u.id IS NULL;
  SELECT COUNT(*) AS orph_items_by    FROM ecocollect_db.claim_items i LEFT JOIN ecocollect_db.users u ON u.id=i.created_by WHERE i.created_by IS NOT NULL AND u.id IS NULL;"
```

- If any orphan check > 0: **stop** — those rows must be repaired in the source first (the new
  FKs would otherwise reject them). Report results to the user before continuing.
- Save the row counts; they are the acceptance baseline (Phase 4 step 4).

## 3. Phase 1 — create the clean schema (schema only, empty target)

Target = `ecocollect_clean`, which is already the `.env` DB name and already empty →
**no `.env` edit needed, no live data involved.**

```powershell
mysql -u root ecocollect_clean < CLEAN_DATABASE.sql
mysql -u root -e "SHOW TABLES IN ecocollect_clean;"   # expect exactly 15
```

Verification queries (all read-only):

```sql
SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='ecocollect_clean';      -- 15
SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
 WHERE TABLE_SCHEMA='ecocollect_clean' AND CONSTRAINT_TYPE='FOREIGN KEY';                  -- 24 FKs
SELECT COUNT(*) FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA='ecocollect_clean' AND INDEX_NAME LIKE 'idx_%';                        -- 11 idx_*
```

Stop here for user sign-off on the created schema before importing any data.

## 4. Phase 2 — data migration (only after sign-off)

Order matters (parents first). Column mapping notes:

| # | Source (`ecocollect_db`) | → Target | Mapping / handling |
|---|---|---|---|
| 1 | `users` | `users` | All columns except `profile_photo` (dropped; source values discarded — they are in the Phase 0 backup) |
| 2 | `students` | `students` | All columns; `gender`/`status` already fit VARCHAR |
| 3 | `student_enrollments` | `student_enrollments` | All columns |
| 4 | `achievements` | `achievements` | All columns |
| 5 | `claim_items` | `claim_items` | All columns |
| 6 | `certificate_templates` | `certificate_templates` | All columns |
| 7 | `certificate_awards` | `certificate_awards` | All columns **except** `school_principal_name`, `program_coordinator_name`, `signatory_position`, 7× `show_*` (backed up in Phase 0) |
| 8 | `bottle_collections` | `bottle_collections` | All columns |
| 9 | `qr_codes` | `qr_codes` | All columns |
| 10 | `student_achievements` | `student_achievements` | All columns |
| 11 | `student_claims` | `student_claims` | All columns (FKs now enforced → Phase 0 orphans must be 0) |
| 12 | `admin_activities` | `admin_activities` | All columns |
| 13 | `system_settings` | `system_settings` | `admin_name`, `school_organization`, `address` only |
| — | `teachers`, `admin_settings`, `awards`, `notification_settings` | *not migrated* | Dead tables (REVIEW §3); their rows stay in the Phase 0 backup |

Mechanics (safe variants — pick one, both are non-destructive):

```powershell
# Option A: per-table copy (INSERT INTO ... SELECT) via mysql client — source untouched
# Option B: mysqldump of individual tables with --no-create-info piped into the new schema
mysqldump -u root --no-create-info --complete-insert ecocollect_db students \
  | mysql -u root ecocollect_clean
# then: ALTER TABLE ... DROP COLUMN for the 17 dead columns in ecocollect_clean
# (only the empty/new target is altered — never the source)
```

Then row-count comparison (must equal Phase 0 counts, except dropped-column tables unaffected):

```powershell
mysql -u root -e "SELECT 'students', COUNT(*) FROM ecocollect_clean.students UNION ALL
  SELECT 'student_enrollments', COUNT(*) FROM ecocollect_clean.student_enrollments UNION ALL
  SELECT 'bottle_collections', COUNT(*) FROM ecocollect_clean.bottle_collections UNION ALL
  SELECT 'certificate_awards', COUNT(*) FROM ecocollect_clean.certificate_awards UNION ALL
  SELECT 'users', COUNT(*) FROM ecocollect_clean.users;"
```

**Cutover** (switch the app to the new data) is a separate, explicit user decision:
either point `DB_DATABASE=ecocollect_clean` (already the case in `.env` — it simply starts
working once data exists) or keep `ecocollect_db` until validated. Never edit `.env` silently.

## 5. Phase 3 — Laravel-side alignment (code changes — after user approval)

1. **Baseline migration** `database/migrations/0001_01_01_000000_create_clean_schema_baseline.php`
   wrapping `CLEAN_DATABASE.sql` in `Schema::hasTable` guards (15 tables) so that
   `php artisan migrate` can build a complete database from scratch — fixes REVIEW §2.
2. **Skip list for legacy migrations** (REVIEW §11): mark as already-run / guard them:
   - `2026_07_06_161936_create_student_enrollments_table` (unguarded duplicate create)
   - `2026_07_01_181735` + `2026_07_01_181950_add_user_id_to_teachers_table` (need `teachers`)
   - `2026_10_18_000001_ensure_users…` (would re-add `users.profile_photo`)
   - `2026_07_06_162525_backfill_student_enrollments` (one-shot data backfill)
3. **Optional dead-code cleanup** (zero behavior change, keep in a separate commit):
   trim `CertificateAward` fillable/casts (10 cols), `Achievement` fillable (`achieved_date`),
   `User` fillable (`profile_photo`), delete `GeneratedReport`/`NotificationSetting` models +
   `User::notificationSettings()`.
4. **Queue decision (REVIEW §10):** recommend `QUEUE_CONNECTION=sync` in `.env.example`
   (documented only — do not rewrite anyone's `.env`), keeping `jobs` tables out.
5. `composer setup` runs `php artisan migrate --force` — after (1)+(2) it becomes correct on
   an empty DB; note this in the setup docs.
6. Keep `tests/Concerns/CreatesApplicationTables.php` as-is (sqlite in-memory suite depends on
   it); optionally drop its `teachers`/`notification_settings` blocks in the same cleanup commit.

## 6. Testing plan (STEP 7)

### T1 — static schema assertions (new read-only script, run against target DB)
- 15 tables exactly; 24 FKs; 11 `idx_*` indexes; no dead tables/columns present;
  collation = `utf8mb4_unicode_ci` on every table; every FK target exists.
- Plus: confirm no `teachers`/`admin_settings`/`awards`/`notification_settings` rows leaked in.

### T2 — existing test suite (must stay green, sqlite in-memory)
```powershell
php artisan test        # expect: 123 passed, 2 skipped, 736 assertions
```
Unchanged code ⇒ this must not move. Re-run after Phase 3 code cleanups too.

### T3 — integration on the clean MySQL schema (the important one)
Run the app against `ecocollect_clean` using a **throwaway env overlay** (never editing `.env`):
```powershell
$env:DB_CONNECTION="mysql"; $env:DB_DATABASE="ecocollect_clean"; $env:APP_ENV="local"
php artisan serve
```
Manual/end-to-end checklist (each maps to a real code path):

| # | Flow | How to verify |
|---|---|---|
| 1 | Setup/registration/login | Setup wizard creates `super_admin`; register → `teacher` (`AuthController.php:91`) |
| 2 | Settings | Save settings → row `id=1` with the 3 columns (`SettingsController`) |
| 3 | Student CRUD + import | Manual create; `StudentsImport` XLSX → `students` + `student_enrollments` (incl. `imported_by/from_file`, `full_name`, `age`, `qr_code`) |
| 4 | Teacher scoping | Teacher sees only own enrolled students; overview shows `teacher_name` |
| 5 | QR generation | Generate QR → `qr_codes` row + SVG on disk (`QrCodeController`) |
| 6 | **IoT scan** | `POST /api/bottle-scan {lrn, bottle_count, points}` → row in `bottle_collections`, `students.total_points` increases inside a transaction; `GET /api/student-points?lrn=` returns the balance; `TRIM(lrn)` lookup matches (`BottleScanController.php:32`) |
| 7 | Dashboard/reports | Charts render (`DAYNAME(collection_date)` on MySQL — the 2 previously-skipped tests now countable) |
| 8 | Claims lifecycle | create → approve → release → archive; `item_name` search; reservation `request_token` idempotency; stock auto `Unavailable` (`ClaimController`) |
| 9 | Achievements | Create quest (`created_by` FK), student progress, `student_achievements` award |
| 10 | **Certificates** | Template upload/saveCanvas → `canvas_data`; **Award to student** (cache-lock fingerprint, private-disk `certificate-awards/generated|pdf`, `issued_by`, `teacher_id` scope); `print`/`file` teacher-ownership 403; `download` picks `pdf_file_path` first; landing/student-award views read `certificate_title`/`signed_by`/`remarks` |
| 11 | Archive flows | Student archive (`status` values), award archive (`status='Archived'`) |
| 12 | Activity log | Actions append to `admin_activities`; feed ordering via `created_at` index |

Caveat: if `php artisan test` is pointed at MySQL, `CreatesApplicationTables` re-creates any
missing table (its `hasTable` guards) and would **mask** a schema gap — that's why T1 (direct
schema assertions) is mandatory, and T3 uses the app itself.

### T4 — data-migration verification (after Phase 2)
- Row-count equality vs Phase 0 (§4).
- Spot checks: 10 random students (name/LRN/points match source), 10 collections, all
  `certificate_awards` (id, student, `award_title`, `pdf_file_path`), last 20 activity rows.
- FK integrity: re-run the Phase 0 orphan queries against `ecocollect_clean` (all 0).
- Dropped-column audit: `SELECT COUNT(*) FROM <backup>…` confirms the discarded values are in
  the Phase 0 backup only.

### T5 — performance sanity
`EXPLAIN` on: `SELECT … FROM bottle_collections WHERE collection_date=…` (uses
`idx_bottle_collections_collection_date`), award list ordered by `awarded_date`
(uses `idx_certificate_awards_awarded_date`). No full scans on those two.

### T6 — regression guard before any cutover
Re-run T2 + T3 end-to-end; only then switch real traffic to `ecocollect_clean`.
Rollback = keep using `ecocollect_db` (never modified) / restore Phase 0 dump.

## 7. Effort/impact summary

| Phase | Changes | Risk |
|---|---|---|
| 0 pre-flight | backup + read-only queries | none |
| 1 create schema | creates 15 tables in the **empty** `ecocollect_clean` | none (empty target) |
| 2 data import | copies into clean target; source untouched | low (backup + row checks) |
| 3 Laravel alignment | 1 baseline migration + guards + optional dead-code trim | medium — gated by T2 |
| 4 cutover | only after T1–T5 pass + explicit approval | controlled, reversible |
