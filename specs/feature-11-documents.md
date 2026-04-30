# Feature 11 — Documents

## Status
⬜ Not started — promoted from later Phase to Phase 1 per Walid 2026-04-30.

## PRD reference
- TASK_MANAGER Feature 11 ("Per-employee document storage with expiry alerts")
- Egyptian Labor Law records-keeping requirements
- Egyptian PDPL (Personal Data Protection Law)

## What this feature does

Per-employee document storage with a **required-documents matrix**. HR uploads scanned copies of physical papers that the employee submitted; the system flags missing required documents per employee and tracks expiry dates on documents that need renewal.

**Critical simplification (Walid 2026-04-30):** because HR physically verifies every document before uploading, the system has **no rejection / pending-review workflow**. Anything in the system is verified by definition. This trims the scope significantly.

## Locked decisions

- **HR-only upload.** Employees and Managers cannot upload documents themselves. Sidebar item hidden from them entirely.
- **Storage:** local disk at `storage/app/employee-documents/{employee_id}/{document_id}.{ext}`
- **File size limit:** 10 MB
- **MIME types:** PDF, JPEG, PNG, WebP
- **Cap:** 30 documents per employee
- **Expiry:** optional date column; expired docs display in red, do NOT block employee status (HR handles offline)
- **Permissions:**
  - `documents.upload` → HR + Admin
  - `documents.view.any` → HR + Admin
  - `documents.delete` → HR + Admin
  - `settings.document_types.manage` → Admin
  - Employees and Managers cannot view any documents (not even their own — Walid's call: HR holds the records, not employee-self-service)

## Required-documents matrix

A **canonical list of document types** lives per-org in a `document_types` table. Each type has:

| Field | Description |
|---|---|
| `name` | Display name (English + Arabic where applicable) |
| `description` | Short HR-facing description |
| `applies_to` | Enum: `all` / `egyptian_only` / `expat_only` / `egyptian_male_only` |
| `is_required` | Boolean |
| `default_expiry_months` | Nullable int — auto-fills the upload form's expiry date |

### Seeded default types (Walid confirmed Egyptian list 2026-04-30)

**For Egyptian employees (required):**

| # | English label | Arabic | Applies to | Default expiry |
|---|---|---|---|---|
| 1 | National ID copy | صورة البطاقة | Egyptian | 7 years |
| 2 | Birth certificate (original on file) | أصل شهادة الميلاد | All | None |
| 3 | Degree certificate (original on file) | أصل شهادة المؤهل | All | None |
| 4 | Social insurance status (NOSI) | برنت تأميني | Egyptian | 12 months |
| 5 | Criminal record clearance — addressed to YZH Solutions | صحيفة الحالة الجنائية | Egyptian | None (one-time, per Walid 2026-04-30) |
| 6 | Employment record / experience stub | كعب العمل | All | None |
| 7 | Health insurance Form 111 | نموذج 111 تأمين صحي | Egyptian | 12 months |

**For expat employees (additional required):**

| # | English label | Applies to | Default expiry |
|---|---|---|---|
| 8 | Passport copy | Expat | Per passport |
| 9 | Work permit | Expat | 12 months (Egyptian law) |
| 10 | Residency permit | Expat | 12 months |

So Egyptians have **7 required docs**, expats have **6 required docs** (3 universal: birth cert, degree, employment record + 3 expat-specific). HR can edit/extend the matrix from Settings → Document types.

### Optional document types (also seeded)

- Driving license (required for drivers / sales reps)
- Marriage certificate (for benefits)
- Birth certificates of dependents (for benefits)
- Performance review (HR-uploaded, internal)
- Disciplinary record (HR-uploaded, internal, sensitive)

## Data model

### `document_types` table

```
id, org_id, name, name_ar (nullable), description, applies_to ('all'|'egyptian_only'|'expat_only'|'egyptian_male_only'),
is_required (bool), default_expiry_months (nullable int), order_index (int), is_active (bool),
version, timestamps, soft_deletes
```

Seeded by `DocumentTypeSeeder`. HR can edit name/required/expiry from Settings.

### `employee_documents` table

```
id, org_id, employee_id (FK), document_type_id (nullable FK — null = optional/uncategorized),
file_path (string), original_filename (string), mime_type, file_size_bytes (int),
issued_date (nullable date), expiry_date (nullable date), notes (text nullable),
uploaded_by_user_id (FK to users), uploaded_at,
version, timestamps, soft_deletes

UNIQUE (org_id, employee_id, document_type_id) — only one document per type per employee.
```

`Auditable` trait writes audit_logs entries on every upload / replace / delete.

## Routes

| Method | URL | Permission | Action |
|---|---|---|---|
| `GET` | `/documents` | `documents.view.any` | All-org documents list (HR/Admin sidebar landing) |
| `GET` | `/employees/{employee}/documents` | `documents.view.any` | Per-employee documents tab |
| `POST` | `/employees/{employee}/documents` | `documents.upload` | Upload a document |
| `PATCH` | `/employees/{employee}/documents/{doc}` | `documents.upload` | Replace file or update metadata |
| `DELETE` | `/employees/{employee}/documents/{doc}` | `documents.delete` | Soft-delete document |
| `GET` | `/employees/{employee}/documents/{doc}/download` | `documents.view.any` | Stream the file |
| `GET` | `/admin/document-types` | `settings.document_types.manage` | Settings page |
| `POST` | `/admin/document-types` | `settings.document_types.manage` | Add new type |
| `PATCH` | `/admin/document-types/{type}` | `settings.document_types.manage` | Edit type |
| `DELETE` | `/admin/document-types/{type}` | `settings.document_types.manage` | Disable type (soft) |

## Frontend pages

1. **`Pages/Documents/Index.tsx`** — sidebar landing for HR/Admin. Lists all documents across employees with filters (employee, type, expiring soon, expired). Engineering Studio drawing-index style.
2. **`Pages/Employees/Show.tsx` — Documents tab** — per-employee. Two sections:
   - **Required documents** — shows the matrix for this employee (Egyptian + expat conditional). Each row: type name, status badge (`Verified` / `Missing — flagged` / `Expired`), expiry date if applicable, Upload button if missing, Replace button if uploaded.
   - **Additional documents** — optional uploads not tied to the matrix (e.g., extra certificates).
3. **`Pages/Settings/DocumentTypes.tsx`** — Admin-only Settings page. Edit the canonical list (name, applies_to, is_required, default expiry).

## Dashboard widget (HR + Admin)

```
00 / Compliance
   12 employees missing required documents
   ┌─ Drill-down: list of employees + which docs each is missing
   ┌─ 5 docs expiring within 30 days
```

Surfaces on HR/Admin dashboard. Clicks through to filtered Documents list.

## Edge cases

- **Replacing a document** — soft-delete the old row, create a new row pointing to the new file. Audit log shows the chain.
- **Employee terminated** — keep all documents (PDPL retention rule TBD with legal counsel; Phase 1 default = retain indefinitely with `employees.deleted_at` not null).
- **Cap reached at 30** — block upload with "Maximum 30 documents per employee. Delete an existing document first."
- **File over 10 MB** — 422 with size-specific message.
- **MIME type mismatch** (e.g., user renames `.exe` to `.pdf`) — server-side magic-byte check rejects.
- **Required document deleted** — type becomes "missing" again, flag re-appears.
- **Document type changed from "required" to "optional"** — existing uploads stay as-is; "missing" flags clear retroactively.

## Test plan

`tests/Feature/Documents/`:

- `DocumentTypeMatrixTest.php` — Egyptian employee sees 7 required types; expat sees 6 (3 universal + 3 expat-specific).
- `DocumentUploadTest.php` — HR can upload PDF/JPEG/PNG/WebP under 10MB; server rejects oversized + wrong MIME.
- `DocumentPermissionsTest.php` — Employee cannot list, view, or download anyone's documents (including their own); Manager cannot either; HR + Admin can.
- `DocumentExpiryTest.php` — expired documents flagged with `expired` status badge; 30-day-warning list correct.
- `DocumentCapTest.php` — 31st upload blocked with clear message.
- `DocumentReplaceTest.php` — uploading same `(employee, type)` pair replaces the file, soft-deletes the old row.
- `DocumentSettingsTest.php` — Admin can add/edit/disable document types; HR cannot.
- `DocumentSoftDeleteTest.php` — soft-deleted documents no longer count toward "uploaded"; required flag returns.

Estimated ~25 tests.

## Implementation notes

- File storage uses `Storage::disk('local')` (defaults to `storage/app/...`). The `backups` disk picks it up via `spatie/laravel-backup`.
- Download endpoint streams the file with original filename; sets `Content-Disposition: attachment` for forced download.
- Required matrix is computed on-demand server-side per employee — no denormalized "compliance status" column. Optimal because the rules can change anytime via Settings.
- Egyptian male check (`applies_to = egyptian_male_only`) — not in the seeded list right now but the column exists for **military service status** (`موقف من التجنيد`) which Walid hasn't explicitly added; placeholder for later.

## Build estimate

~1 day: schema + migrations (2hr) + N-tier backend (3hr) + frontend pages (4hr) + Settings page (2hr) + tests (3hr) + dashboard widget (1hr).
