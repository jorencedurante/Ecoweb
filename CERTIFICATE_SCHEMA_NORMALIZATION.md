# CERTIFICATE_SCHEMA_NORMALIZATION.md
## Review of `certificate_templates` + `certificate_awards` — EcoCollect

**Status: APPROVED AND APPLIED (2026-10-23).** All 15 code changes are in place; the
cleanup migration was executed on `ecocollect_clean` with both flags ON (final shape:
awards **16 columns**, templates **11 columns**, 4 FKs). `ecocollect_db` and Railway were
never modified; no rows were deleted (both tables were empty in `ecocollect_clean`; the
backfill phases were no-ops). Full test suite after application: **123 passed,
2 skipped, 736 assertions** (identical to baseline). `CLEAN_DATABASE.sql` and
`CLEAN_DATABASE_SCHEMA.md` refreshed to the final shape.
Target database: `ecocollect_clean` (local test DB only). `ecocollect_db` was read-only
(inspected via `SELECT` only). No new tables. Rollback path: `migrate:rollback --path=…`
re-adds all dropped columns/FKs as nullable.

Method: every model, controller, migration, Blade view, route, test, seeder and raw SQL
reference to both tables was inventoried (TASK 1), then live data was profiled
(read-only queries against `ecocollect_db`) to confirm what the columns actually contain.

---

# TASK 1 — Complete reference inventory

## 1.1 `certificate_templates` (12 columns)

| Column | Written by | Read/queried by | Verdict |
|---|---|---|---|
| `id` | PK | routes/models | keep |
| `template_name` | `CertificateController.php:410` (`storeTemplate`) | `:296,358` orderBy, `:359` JSON list, `:431` JSON response | keep |
| `description` | `:411` | — (metadata only) | keep (user-entered) |
| `file_path` | `:412` (public disk `certificate-templates/`) | `:359` list JSON | keep |
| `file_type` | `:413` (`getClientOriginalExtension()`) | `:359` list JSON | keep |
| `status` | `:414` always `'active'` | `:290,352` `where('status','active')` | keep |
| `visibility` | `:415` `'global'`/`'private'` (`isAdminLevel`) | `:293,355` teacher scoping | keep |
| `template_type` | `:416` always `'uploaded'` | never | keep (fixed vocab) |
| `uploaded_by` | `:417` | `:293,355` `orWhere('uploaded_by', Auth::id())`; `CertificateTemplate::uploader()` (unused) | **canonical user column** |
| `created_by` | `:418` — **always identical to `uploaded_by`** | `CertificateTemplate::creator()` — **never called** | **DROP candidate** |
| `created_at`/`updated_at` | Eloquent | — | keep |

FKs: `created_by`→users, `uploaded_by`→users (both SET NULL).
Model `CertificateTemplate.php`: fillable lists all; relations `uploader()`, `creator()`, `awards()`
— only `awards()` is referenced (by nothing outside the model file).

## 1.2 `certificate_awards` (23 columns in `ecocollect_clean`)

| Column | Written by (active code) | Read by | Verdict |
|---|---|---|---|
| `id` | PK | routes | keep |
| `student_id` | `store():58`, `saveCanvas():113`, `award():242`, `updateCanvas():336` | `StudentController.php:535,615,726`; `PublicController.php:123,347`; `Student::certificateAwards()` | keep |
| `teacher_id` | `store():59`, `saveCanvas():114`, `award():243` (only when Auth is teacher) | 403 ownership checks `:286,305,367,444,455,470`; overview `StudentController.php:727-728,738` (`canAccess`, `teacher_name`) | keep (adviser/owner scope) |
| `award_title` | `store():60`, `saveCanvas():116`, `award():246`, `updateCanvas():323` | `student-awards.blade:29`; `landing:153`; overview `:651,732`; `download():373` | keep (canonical title) |
| `award_description` | `:61,:117,:324` | overview `:733`; landing `:154` | keep |
| `certificate_file` | **`store():63`** (legacy upload flow — public disk `certificates/`) | fallback chains `download():388`, `certificate-print:10`, `student-awards:53`, overview `hasFile:728` | keep (active flow + history) |
| `awarded_by` (text) | `award():245` (`Auth::user()->name`) | **never read anywhere** (live data: NULL in all 5 rows) | **DROP candidate** (dup of `issued_by`) |
| `template_file_path` | **never written by any code** | fallback chains only `:388`, `certificate-print:10`, `student-awards:53`, `landing:162` (live: NULL everywhere) | **DROP candidate** |
| `template_file` | `saveCanvas():119` (public), `award():248` (private), `updateCanvas():340` (public) | `certificate-editor:28-29,94-95`; `certificate-print:10`; `student-awards:53`; `landing:162`; `download():388`; overview `:728` | keep (canvas background) |
| `canvas_data` | `:120,:251,:325` (JSON) | `certificate-print:14`; `certificate-editor:451`; overview | keep (drag/drop + fonts) |
| `awarded_date` | `:62,:118,:247` | `:565,:617,:646-647,:737`; views | keep |
| `certificate_type` | **never written** | `StudentController.php:652,734` (renders null) | **DROP candidate** (write-never slot) |
| `certificate_title` | **never written** (`store()` maps its form field to `award_title`) | fallbacks `student-awards:29`, `landing:153` | **DROP candidate** |
| `generated_file_path` | `award():249` (private disk) | `download():382`; `file():459`; `certificate-print:143`; overview `hasFile:728` | keep (composite render) |
| `pdf_file_path` | `award():250` (private disk, jpg→PDF) | `download():378` (first choice) | **CANONICAL completed file** |
| `status` | `:64,:122,:253` `'Active'`; `archive():473` `'Archived'` | archive filters; overview `:736` | keep |
| `issued_by` | `:65,:115,:244` (`Auth::id()`) | `print()` `load('issuer')`; relation `issuer()` | **CANONICAL issuing user** |
| `signed_by` | `saveCanvas():121`, `award():252` (adviser name snapshot) | `student-awards:38-40`; `print()` view | keep (signatory) |
| `school_year` | **never written** | `StudentController.php:735` (renders null) | **DROP candidate** (write-never slot) |
| `remarks` | **never written** | `student-awards:49-50` (renders null) | **DROP candidate** (write-never slot) |
| `template_id` | **never written by any code path** (`store/saveCanvas/award/updateCanvas` all store a `template_file` string instead); seeder sets `null`; live: NULL in 100% of rows | `CertificateAward::template()` relation (never called) | **DROP candidate** (dead FK) |
| timestamps | Eloquent | — | keep |

FKs today: `student_id`→students CASCADE, `template_id`→templates SET NULL, `teacher_id`→users SET NULL, `issued_by`→users SET NULL.

## 1.3 Flows → columns map (who actually exercises what)

| Flow | Route / entry | Columns written | Columns read |
|---|---|---|---|
| **Auto Generated Certificate (editor)** | `certificate.blade:20` form → `admin.certificate.save-canvas` | student_id, teacher_id, issued_by, award_title, award_description, awarded_date, `template_file` (public), `canvas_data`, `signed_by`, status | canvas background = posted file |
| **Award to Student** | `certificate.blade:1400` fetch → `admin.certificate.award` | student_id, teacher_id, issued_by, ~~awarded_by~~, award_title, awarded_date, `template_file`+`generated_file_path`+`pdf_file_path` (private), `canvas_data`, `signed_by`, status | server validates canvas boxes (student/adviser ids, positions) |
| **Print Preview** | client-side JS in `certificate.blade` (no DB) | — | in-memory canvas |
| **Print** | `admin.certificate.print` (`student-awards:58`) | — | teacher_id (403), `generated_file_path` (`:143`, via `file()`), else `template_file ?? certificate_file` (`:10`) + `canvas_data` (`:14`) |
| **Download** | `admin.certificate.download` (`student-awards:55-56`) | — | teacher_id (403), `pdf_file_path` → `generated_file_path` → `template_file ?? certificate_file` |
| **Retrieve student certificates** | `StudentController::awards()` (`:531`) + overview (`:615-738`) + public landing (`PublicController:123,347`) | — | student_id, award_title, award_description, status, awarded_date, teacher_id/teacher.name, ~~certificate_title~~, ~~certificate_type~~, ~~school_year~~, ~~remarks~~, file fallback chain |
| Legacy certificate upload (backend + tests only) | `admin.certificate.store` (no view calls it; `CertificateTest` does) | student_id, teacher_id, award_title, award_description, awarded_date, `certificate_file`, status, issued_by | — |
| Legacy edit award | `admin.certificate.update-canvas` (`certificate-editor:11`) | award_title, award_description, canvas_data, student_id, template_file | teacher_id 403 |
| Template library upload | `admin.certificate.template.store` (**no view/JS calls it**) | all template columns incl. `uploaded_by`+`created_by` | — |
| Template list | `admin.certificate.templates.list` + `edit()` (**no view/JS calls them**; `$templates` unused in `certificate-editor.blade`) | — | status, visibility, uploaded_by scoping |

## 1.4 Live data profile (read-only, `ecocollect_db`)

- `certificate_templates`: **0 rows**.
- `certificate_awards`: 5 rows (ids 8,9,10,12,13):
  - `template_id` **NULL in 100%** of rows; `template_file` **populated in 100%** (all under public `certificate-templates/…`);
  - `awarded_by`, `certificate_file`, `template_file_path`, `generated_file_path`, `pdf_file_path`: **NULL in 100%**;
  - rows 8–10: design-only saves (no student); rows 12–13: design saves with student + adviser (`signed_by`='Karen', `issued_by`=6) — i.e. `saveCanvas` output, not `award()` output.
- Conclusion: the only file reference the historical data relies on is **`template_file`**, and the only user reference is **`issued_by`** (+ `signed_by` text snapshot). Dropping the columns proposed below loses **zero** stored data.

---

# TASK 2 — Attributes required by the two workflows

**Auto Generated Certificate (save design):** `student_id` (opt), `teacher_id` (opt), `issued_by`, `award_title`, `award_description` (opt), `awarded_date`, `template_file`, `canvas_data`, `signed_by`, `status`.

**Award to Student:** everything above plus `generated_file_path`, `pdf_file_path` (canonical outputs). Server-side validation also depends on `canvas_data` box ids (student_id/teacher_id inside JSON) — schema untouched.

**Shared read set for Print/Download/Retrieve:** `teacher_id` (ownership), `pdf_file_path` → `generated_file_path` → `template_file ?? certificate_file` (file fallback), `canvas_data`, `award_title`, `award_description`, `awarded_date`, `status`, `issued_by`/`signed_by`.

Nothing in either workflow needs: `template_id`, `awarded_by`, `template_file_path`, `certificate_title`, `certificate_type`, `school_year`, `remarks`, `certificate_templates.created_by`.

---

# TASK 3 — Is `certificate_templates` a reusable-design library? Is `template_id` populated?

- **By design, yes:** `storeTemplate` persists reusable designs with `visibility` (`global`/`private`) and `status` (`active`), and `edit()`/`templatesList()` run correctly scoped queries for a picker.
- **In practice, dormant:** no Blade/JS anywhere calls `admin.certificate.template.store` or `admin.certificate.templates.list`; `$templates` is passed to `certificate-editor` but never rendered; the live table has **0 rows**.
- **`template_id` is never populated:** no controller writes it, the seeder writes `NULL`, live data is 100% NULL. Awards never reference library rows — each award/save keeps its own `template_file` copy (the actual working pattern, including its dedupe fingerprint which hashes the uploaded file).
- **Recommendation:** keep the table + endpoints (no new tables; a future picker UI can use them) but drop the dead `created_by` duplicate; drop the never-populated `certificate_awards.template_id` FK. *(If you want to keep `template_id` for a future library-linked editor, say so — it is the one judgment call in this plan.)*

---

# TASK 4 — Canonical columns

| Purpose | Canonical column | Rule |
|---|---|---|
| **Completed certificate file** | **`pdf_file_path`** (private disk `certificate-awards/pdf/…`) | New award writes always attempt it; `download()` already prefers it. Read fallback (kept, unchanged, for history): `pdf_file_path` → `generated_file_path` → `template_file ?? certificate_file`. |
| **Issuing user** | **`issued_by`** (FK → `users`, SET NULL, `CertificateAward::issuer()`) | Every write path already sets it. The text duplicate `awarded_by` (never read, NULL in all live rows) is removed. `signed_by` stays as the *adviser signatory* snapshot; `teacher_id` stays as the *ownership/scope* FK — different meanings, both required. |

---

# TASK 5 — Historical records & foreign keys

- **No rows are deleted or modified except two additive backfills** (both no-ops on current data, idempotent):
  1. `award_title = certificate_title` where `award_title` is empty and `certificate_title` isn't;
  2. `signed_by = awarded_by` where `signed_by` is NULL and `awarded_by` isn't.
- **FKs preserved:** `student_id`, `teacher_id`, `issued_by` untouched; file paths and files on disk untouched (public `certificates/`, `certificate-templates/`; private `certificate-awards/{templates,generated,pdf}/`).
- **FKs retired (both completely unused, zero data):** `certificate_awards.template_id` (100% NULL, never written), `certificate_templates.created_by` (always equals `uploaded_by`, relation never called).
- Historical rows keep working through the existing fallback chain (all 5 live rows have `template_file`).
- `ecocollect_db` and Railway are untouched; when they are later migrated, the already-documented import mapping applies (legacy columns are backed up in the Phase-0 dump before any drop).

---

# TASK 6 — Cleanup migration (drafted, NOT executed)

**File:** `database/migrations/2026_10_23_000100_normalize_certificate_tables.php` — **executed
2026-10-23 with both flags ON** against `ecocollect_clean` (recorded in its `migrations`
table). Phase A backfills were no-ops (both tables empty).

| Phase | Guard | Action |
|---|---|---|
| A (always) | `hasColumn` checks | non-destructive backfills (TASK 5) |
| B | `env('CERT_SCHEMA_CLEANUP_APPLY', false)` = **false by default** | drop `certificate_awards.template_id` (+FK), `awarded_by`, `template_file_path`; drop `certificate_templates.created_by` (+FK) |
| C | `env('CERT_SCHEMA_CLEANUP_DROP_METADATA', false)` = **false by default** | drop `certificate_awards.certificate_title`, `certificate_type`, `school_year`, `remarks` (write-never slots; views render null safely) |
| down() | — | re-adds all dropped columns/FKs as nullable (rollback safety) |

Running `php artisan migrate` today therefore performs **only** the harmless backfills.
Destructive steps require explicitly setting the env flag(s), e.g.:

```powershell
# after final schema approval:
$env:CERT_SCHEMA_CLEANUP_APPLY="true"; $env:CERT_SCHEMA_CLEANUP_DROP_METADATA="true"
php artisan migrate     # target remains ecocollect_clean (.env)
```

**Sequencing note:** Phase A has already been executed once against the (currently empty)
`ecocollect_clean` (verified: 23 award columns intact, 0 rows, migration recorded). If
certificate data is later imported into `ecocollect_clean`, re-apply the backfills with
`php artisan migrate:rollback --path=database/migrations/2026_10_23_000100_normalize_certificate_tables.php`
followed by `php artisan migrate --path=…` (or make the import mapping set canonical
`award_title`/`signed_by` directly).

The migration is `hasColumn`-guarded, so it is also shape-safe (no-op where columns are absent),
but it is intended **only** for `ecocollect_clean` — never run it against `ecocollect_db` or
Railway without separate review.

---

# TASK 7 — Code changes applied (2026-10-23)

All 15 changes below are **in place** (change #15 applied as well: `edit()` no longer
passes the unused `$templates`). During application the migration's `down()` also received
a duplicate-FK guard fix (`Schema::getForeignKeys` check) so rollback cannot re-create an
existing constraint.

| # | File:line | Change |
|---|---|---|
| 1 | `app/Http/Controllers/CertificateController.php:245` | remove `'awarded_by' => Auth::user()->name` from `award()` |
| 2 | `app/Http/Controllers/CertificateController.php:388` | `download()` fallback → `$award->template_file ?? $award->certificate_file` |
| 3 | `app/Http/Controllers/CertificateController.php:417-418` | `storeTemplate()` keeps `uploaded_by` only (drop `created_by`) |
| 4 | `app/Http/Controllers/StudentController.php:728` | `hasFile` chain → `generated_file_path ?? template_file ?? certificate_file` |
| 5 | `resources/views/pages/certificate-print.blade.php:10` | fallback → `template_file ?? certificate_file` |
| 6 | `resources/views/pages/student-awards.blade.php:29` | `{{ $award->award_title }}` (drop `certificate_title` fallback) |
| 7 | `resources/views/pages/student-awards.blade.php:53` | fallback → `template_file ?? certificate_file` |
| 8 | `resources/views/pages/landing-student-details.blade.php:153` | `{{ $award->award_title }}` (drop `certificate_title`/dead `title` fallbacks) |
| 9 | `resources/views/pages/landing-student-details.blade.php:162` | `template_file` only |
| 10 | `app/Models/CertificateAward.php` | trim fillable to kept columns; remove `show_*` casts; remove `template()` relation |
| 11 | `app/Models/CertificateTemplate.php` | trim fillable (drop `created_by`); remove `creator()`, `awards()` relations |
| 12 | `database/seeders/AwardSeeder.php:14,20,26,32,38` | remove `'template_id' => null` |
| 13 | `tests/Feature/CertificateAwardToStudentTest.php:120` | remove `'awarded_by' => $admin->name` from `assertDatabaseHas` |
| 14 | `tests/Concerns/CreatesApplicationTables.php:159,166-167,178-179,186-187` (+ `show_*`, templates `created_by`) | align sqlite test schema with the after-shape |
| 15 | (optional) `CertificateController.php:290-299` | stop passing unused `$templates` to `certificate-editor` |

Notes:
- Views at #5–#9 keep working even without edits (missing attribute → `null`), but the cleanups remove misleading dead fallbacks.
- `CertificateTest` posts `certificate_title` as a **form field** (maps to `award_title`) — unaffected.
- `BottleCollectionController.php:29`-area `awarded_by`/`awarded_date` writes belong to **`student_achievements`**, a different table — untouched (Achievement Quests preserved as required).
- After approval also refresh `CLEAN_DATABASE.sql` / `CLEAN_DATABASE_SCHEMA.md` to the after-shape.

# Before / After schema comparison

## `certificate_templates` — 12 → 11 columns

| | Columns |
|---|---|
| **Before** | id, template_name, description, file_path, file_type, status, visibility, template_type, created_by, **uploaded_by**, created_at, updated_at |
| **After** | id, template_name, description, file_path, file_type, status, visibility, template_type, **uploaded_by**, created_at, updated_at |
| **Removed** | `created_by` + its FK (always duplicated `uploaded_by`; `creator()` never called) |

## `certificate_awards` — 23 → 16 columns (with both flags on)

| | Columns |
|---|---|
| **Before** | id, student_id, **template_id**, teacher_id, award_title, award_description, certificate_file, **awarded_by**, **template_file_path**, template_file, canvas_data, awarded_date, **certificate_type**, **certificate_title**, generated_file_path, **pdf_file_path**, status, issued_by, signed_by, **school_year**, **remarks**, created_at, updated_at |
| **After** | id, student_id, teacher_id, award_title, award_description, certificate_file, template_file, canvas_data, awarded_date, generated_file_path, **pdf_file_path** *(canonical file)*, status, **issued_by** *(canonical issuer)*, signed_by, created_at, updated_at |
| **Removed (flag B)** | `template_id`+FK (never written, 100% NULL), `awarded_by` (never read, NULL in all rows), `template_file_path` (never written) |
| **Removed (flag C)** | `certificate_title`, `certificate_type`, `school_year`, `remarks` (never written; views tolerate absence) |

**Result:** one file column does the durable work (`pdf_file_path`, with a documented 3-step
fallback for history), one user column records the issuer (`issued_by`), `template_file` remains
the per-award canvas background, `teacher_id`/`signed_by` keep their distinct scope/signatory
roles. Award to Student, Print Preview, Download, Print, drag-and-drop positioning and font
settings (`canvas_data`) are untouched.

---

# TASK 8 — Verification results

**Executed after application (code + flags-on migration):**
- Full suite: **123 passed, 2 skipped, 736 assertions** — identical to the pre-change baseline.
- `php -l` clean on all 7 modified PHP files; residual-reference sweep clean (only remaining
  `certificate_title` hits are the `store()` *form field* name that maps to `award_title`).
- Schema assertions on `ecocollect_clean`: awards = **16 columns**, templates = **11
  columns**; FKs = `student_id`, `teacher_id`, `issued_by` (awards) + `uploaded_by`
  (templates); `template_id`/`created_by` FKs gone; both tables empty (0 rows) before and
  after — zero data deleted.

**Post-application manual checklist (against `ecocollect_clean` app run):**

| # | Check | How |
|---|---|---|
| 1 | Upload template (editor bg + library route) | editor upload → preview; `POST /certificate/template` still creates a row with `uploaded_by` only |
| 2 | Select student + adviser | saveCanvas + award both store `student_id`, `signed_by` (adviser), `issued_by` |
| 3 | Adjust fonts + name positions | `canvas_data` round-trips through save/edit/print (assert JSON boxes + positions) |
| 4 | Print Preview | client-side composite renders (no DB change) |
| 5 | Award to Student | POST `/certificate/award` → row with `template_file`, `generated_file_path`, `pdf_file_path` on private disk; duplicate fingerprint still blocks |
| 6 | Retrieve student certificates | `StudentController::awards()` + overview JSON (`hasFile` true) + landing page list |
| 7 | Download completed certificate | `download()` serves PDF first (`%PDF`/`%%EOF`), image fallback works for png-awards, legacy `template_file` fallback works for historical rows |
| 8 | Print completed certificate | `print()` page renders `file()` image first, legacy reconstruction fallback intact |
| 9 | Regression | full `php artisan test` green (done); Achievement Quests tests green (untouched) |

**Rollback:** `php artisan migrate:rollback` re-adds dropped columns (nullable) + FKs; the
env flags make accidental execution impossible; `ecocollect_db`/Railway never in scope.
