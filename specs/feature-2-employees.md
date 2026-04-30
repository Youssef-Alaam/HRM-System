# Feature 2 — Employees

## Status
🟨 In progress

## PRD reference
- PRD §3.2 (permission matrix), §8 Feature 2
- TASK_MANAGER Feature 2 done-when checklist
- Decision 13 (NULL manager auto-approves)
- Decision 18 (universal law-minimum leave balances on day 1)
- EGYPT_COMPLIANCE_RULES — National ID 14 digits, phone format

## User stories per role

- **Employee:** view their own profile (`/employees/me` or `/employees/{id}` if self), edit Tier 1 fields directly (phone, address, emergency contact), submit a Tier 2 change request (marital, dependents, bank) for HR approval. Cannot view other employees' profiles.
- **Manager:** view team profiles (employees where `manager_id = my.employee.id`); same Tier 1 self-edit; cannot view non-team profiles.
- **HR:** full CRUD — list all, create new (with photo upload, validation), edit any (Tier 1/2/3), soft-delete with reason, terminate, confirm deemed resignation. Tier 3 = HR-only fields (salary, contract terms, role assignment).
- **Admin:** full HR access + restore soft-deleted + override foreign-quota.

## Routes (REST under `/employees`)

| Method | URL | Permission | Action |
|---|---|---|---|
| `GET` | `/employees` | `employees.view.team` or higher | List (filtered by scope) |
| `GET` | `/employees/create` | `employees.create` | Create form |
| `POST` | `/employees` | `employees.create` | Store new employee + user + initial leave balance |
| `GET` | `/employees/{employee}` | view permission | Detail page |
| `GET` | `/employees/{employee}/edit` | edit permission (tier-aware) | Edit form |
| `PATCH` | `/employees/{employee}` | edit permission (tier-aware) | Update — Tier 1 self-edit goes through immediately; Tier 2 self-edit creates a change request; Tier 3 requires HR/Admin |
| `DELETE` | `/employees/{employee}` | `employees.delete` | Soft-delete with required reason |
| `POST` | `/employees/{employee}/restore` | `employees.restore` (Admin only) | Restore soft-deleted |
| `POST` | `/employees/{employee}/terminate` | `employees.terminate` | Termination flow (compliance workflow stub here, full in Feature 17) |

## Data sources

| What | Source |
|---|---|
| Employees list | `employees` table, OrgScope-filtered, soft-delete-aware |
| Roster filters | `employees` joined `departments` + `positions` |
| Search | LIKE on name + email + employee_code |
| Photo storage | `storage/app/public/employees/{org_id}/{employee_id}/photo.{ext}` (Spatie media-library deferred to later feature) |
| User account | `users` table — created in same transaction as employee, role assigned via Spatie |
| Initial leave balance | per Decision 18 — written by `LeaveService::initializeBalances()` (stub for now; real impl in Feature 6) |

## Validation rules (`StoreEmployeeRequest`)

- `first_name`, `last_name` — required, string, max 100
- `email` — required, email, unique on `users.email`
- `national_id` — required, regex `/^[2-3]\d{13}$/` (Egyptian 14-digit, starts 2 or 3)
- `phone_primary` — required, regex `/^01[0125]\d{8}$/` (Egyptian 11-digit mobile)
- `phone_secondary` — nullable, same regex
- `date_of_birth` — required, date, before today, after 1940-01-01
- `gender` — required, in:`male,female`
- `marital_status` — required, in:`single,married,divorced,widowed`
- `nationality` — required, string (default "Egyptian")
- `is_expat` — boolean
- `passport_number` — `required_if:is_expat,true`, max 20
- `work_permit_number` — `required_if:is_expat,true`, max 30
- `work_permit_expiry` — `required_if:is_expat,true`, date, after today
- `department_id` — required, exists:departments,id (org-scoped)
- `position_id` — required, exists:positions,id (org-scoped)
- `office_id` — required, exists:offices,id (org-scoped)
- `manager_id` — nullable, exists:employees,id (org-scoped)
- `hire_date` — required, date
- `base_salary_piasters` — required, integer, min 0 (HR/Admin only — Tier 3)
- `photo` — nullable, image, max 4MB, mimes:jpeg,png,webp

Same rules for `UpdateEmployeeRequest`, with `email` unique excluding the current user's id.

Tier-edit gating: a `BaseFormRequest::authorize()` checks the requested fields against the user's permission set:
- Tier 1 fields (`phone_primary`, `phone_secondary`, `current_address`, `emergency_contact_*`) — pass if `employees.edit.own.tier1` AND target is self.
- Tier 2 fields (`marital_status`, `dependents`, `bank_account`) — pass if `employees.edit.own.tier2` AND target is self → enqueue an approval request instead of direct write.
- Tier 3 fields (`base_salary_piasters`, `manager_id`, `position_id`, `department_id`, `is_expat`, work-permit) — require `employees.edit.any`.

## Service layer

`EmployeeService::create(array $data, ?UploadedFile $photo): Employee`
- Wraps in `transaction()`:
  1. `User::create()` with email + password (random, force-reset on first login)
  2. `Employee::create()` linked to that user
  3. `User->assignRole('employee')` (or whatever role from the payload, HR-only)
  4. `Employee->update(['user_id' => ...])` close the loop
  5. Photo upload if present → `storage/app/public/employees/...`
  6. `LeaveService::initializeBalances($employee)` per Decision 18
  7. `Notification::send(...)` welcome email (deferred — emit job, fail-soft)
- `Auditable` trait writes the audit row for the employee creation.

`EmployeeService::softDelete(int $id, string $reason)`
- Wraps in transaction. Writes `deleted_reason` column. Cascades the linked user account: disable login (set `users.disabled_at`), do NOT delete the user (audit trail preserved).

`EmployeeService::restore(int $id)` — Admin only per `employees.restore`.

`EmployeeService::terminate(int $id, array $data)` — stub for the full Feature 17 compliance workflow; for now records the termination date and reason.

## Edge cases

- Duplicate email on create — surface 422 with field-level error
- National ID checksum (Egypt's 14-digit ID encodes governorate + birth date) — basic regex now; full checksum validation deferred unless Walid wants it earlier
- Photo upload failure mid-transaction — rollback + clean up partial file
- Manager_id pointing to a soft-deleted employee — block with validation
- Self-set manager_id (employee tries to edit their own manager) — block in tier authorization
- Cyclic management graphs (A → B → A) — validated in service before save
- Foreign quota override (Decision/ANA-3.20) — defer to Feature 19
- Photo > 4MB — 422 with size-specific message
- Department in a different org than current user — `exists` rule with org scope rejects
- User has no employee record yet (e.g. first admin) — create flow allowed; admin can create themselves an Employee row

## Acceptance criteria

- [ ] All 9 routes wired
- [ ] `StoreEmployeeRequest` + `UpdateEmployeeRequest` validate per the rules above; Zod schemas mirror on the frontend
- [ ] `EmployeeService::create` runs in a transaction, creates User + Employee + role + balance + photo + audit log
- [ ] Soft-delete flow records reason and disables linked user
- [ ] Tier 1 self-edit goes through immediately
- [ ] Tier 2 self-edit creates a change request (Feature 14 will surface the approval queue; for now request row is written and visible to HR via list filter)
- [ ] Tier 3 fields require HR/Admin permission
- [ ] Photo upload + preview works; <4MB enforced
- [ ] List view supports search + filter by department / position / office / status
- [ ] Detail view has tabs (per TASK_MANAGER): Profile / Attendance / Leave / Documents — Attendance + Leave + Documents tabs are stubs until those features ship
- [ ] All Pest tests pass
- [ ] Engineering Studio aesthetic — section identifiers, monospace metadata, stamped CTAs, no card grids
- [ ] Mobile responsive at 375px

## Compliance citations

- Decision 18 — universal law-minimum leave balances initialized on create
- Decision 13 — `manager_id IS NULL` employees auto-approve their own requests (relevant when Feature 6/7 land; encoded in service flag now)
- Egyptian Labor Law Art. 5 (employee records) — basic identity fields required; mirror what the law mandates be on file

## Test plan

`tests/Feature/Employees/EmployeeCreateTest.php`:
- HR can create an employee with all required fields → user + employee + role + balance + audit row exist
- HR cannot create with duplicate email
- HR cannot create with invalid national ID
- Expat employee requires passport + work permit + expiry
- Non-expat employee passes without passport
- Photo uploads and stored under correct path
- Photo > 4MB rejected
- Service rolls back on transaction failure (mock LeaveService throw → no User row left)

`tests/Feature/Employees/EmployeeListTest.php`:
- Employee sees only their own row
- Manager sees their team
- HR sees all in their org
- Multi-tenancy: HR in org A doesn't see employees in org B
- Soft-deleted employees not in default list; visible with `?include_deleted=1` for Admin

`tests/Feature/Employees/EmployeeUpdateTest.php`:
- Employee can update own Tier 1 fields directly
- Employee cannot update own Tier 2 fields directly (creates request instead)
- Employee cannot update Tier 3 fields
- HR can update any tier
- Updating a non-self employee as Employee role is blocked

`tests/Feature/Employees/EmployeeDeleteRestoreTest.php`:
- HR soft-deletes with reason; reason persisted; linked user disabled
- Soft-delete without reason rejected
- Admin can restore; HR cannot
- Restore re-enables linked user

## Implementation notes

- The 54-column `employees` table from F2 already has all the fields. No new migration needed for the create flow.
- Tier-aware authorization lives in a custom `EmployeeFormRequest::authorize()` rather than a Policy, because the tier depends on which fields are present in the payload.
- Photo upload uses `Storage::disk('public')->put(...)` for now; Spatie media-library can replace later if multi-photo galleries are needed.
- Frontend list page uses the new `useForm`-driven inertia search; debounced 200ms, persists in URL.
- Frontend detail page uses tabs (Inertia partial reload per tab to avoid full page reloads).

This is a 1-2 day feature realistically. Pest test count expected: +20 to +30 new tests. File count: ~12 new (controller + 2 form requests + service + repo + interface + 4 React pages + 4 test files + factory updates).
