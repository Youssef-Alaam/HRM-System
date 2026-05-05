# Feature 6 — My Leave

## Status
⬜ Not started

## PRD reference
- PRD §8 Feature 6-8
- TASK_MANAGER Feature 6 done-when checklist
- DECISIONS.md: Decision 13 (NULL-manager auto-approve), Decision 18 (universal balances), Decision 19 (discretion vs rights)
- EGYPT_COMPLIANCE_RULES.md: Art. 89 (annual), Art. 54 (sick), Art. 90 (casual), Art. 93 (maternity), Art. 93 bis (paternity), Art. 94 (study)

## User story
An authenticated employee visits `/my-leave` and can:
1. See their leave requests in three tabs: Pending / Approved / Rejected (via `?tab=` param)
2. Submit a new leave request via "New Leave Request" form
3. Cancel a pending request directly
4. View their balance per leave type in the form

## Schema

### `leave_types` table
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| org_id | bigint FK | BelongsToOrg |
| code | varchar(50) | unique per org |
| name | varchar(200) | |
| default_balance_days | integer nullable | null = unlimited / accrual-based |
| requires_certificate_after_days | integer nullable | sick: 3 |
| advance_notice_days | integer nullable | study: 10 |
| is_right_not_discretion | boolean | sick/casual/maternity/paternity = true |
| applies_to | enum: all/male/female | maternity = female |
| sort_order | smallint | |
| is_active | boolean | |
| version | integer | optimistic concurrency |
| timestamps + softDeletes | | |

### `leave_requests` table
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| org_id | bigint FK | BelongsToOrg |
| employee_id | bigint FK | |
| leave_type_id | bigint FK | |
| start_date | date | |
| end_date | date | |
| days_count | integer | excludes weekends + holidays |
| status | enum: pending, approved, rejected, cancelled | |
| reason | text nullable | |
| attachment_path | varchar nullable | medical cert, PDF/image |
| approved_by_user_id | bigint FK nullable | |
| approved_at | timestamp nullable | |
| rejected_reason | text nullable | |
| cancelled_at | timestamp nullable | |
| version | integer | optimistic concurrency |
| timestamps + softDeletes | | |

## Leave types seeded (6 minimum per Egyptian Labor Law)

| Code | Name | Balance | Notes |
|---|---|---|---|
| annual | Annual Leave | 21 days | Per Art. 89 — discretionary |
| sick | Sick Leave | 90 days | Per Art. 54 — right |
| casual | Casual Leave | 7 days | Per Art. 90 — right |
| maternity | Maternity Leave | 120 days | Per Art. 93, Law 29/2025 — female only |
| paternity | Paternity Leave | 3 days | Per Art. 93 bis — right |
| study | Study Leave | 10 days | Per Art. 94 — 10-day advance notice required |

## Day-count helper
`LeaveService::countWorkdays(string $start, string $end, int $orgId): int`
- Iterates from start to end inclusive
- Excludes Fri + Sat (Egyptian weekend default)
- Excludes dates found in `holidays` table for the org
- Returns integer count

## Validation rules (FormRequest `StoreLeaveRequest`)
- `leave_type_id` must exist in `leave_types` for the org and be active
- `start_date` >= today
- `end_date` >= `start_date`
- Cannot exceed remaining balance for the type (balance = default_balance_days - approved days used)
- Cannot overlap existing pending or approved requests for the same employee
- `leave_type.requires_certificate_after_days`: if `days_count >= requires_certificate_after_days`, `attachment` required (PDF/JPEG/PNG/WebP, max 10 MB)
- `leave_type.advance_notice_days`: if non-null, `start_date >= today + advance_notice_days`
- `applies_to` gender gate: maternity requires employee gender = female

## Decision 13: NULL-manager auto-approve
When the leave request is created and `employee.manager_id IS NULL`, the request is immediately set to `status = approved`, `approved_by_user_id = null`, `approved_at = now()`, and balance is decremented. HR receives an FYI notification (log entry). This runs inside `LeaveService::create()`.

## Cancel flow
- `POST /my-leave/{id}/cancel` — cancel pending request (sets status = cancelled, cancelled_at = now, soft-delete)
- `POST /my-leave/{id}/cancel-with-override` — cancel approved request; gated `leave.edit.any` (HR only)

## Routes
- `GET /my-leave` — replaces `placeholder.leave`, permission: `leave.view.own`
- `POST /my-leave` — store new request, permission: `leave.request.own`
- `POST /my-leave/{id}/cancel` — cancel own pending, permission: `leave.request.own`
- `POST /my-leave/{id}/cancel-with-override` — cancel approved (HR), permission: `leave.edit.any`

## Acceptance criteria
- [ ] Migrations for `leave_types` and `leave_requests`
- [ ] Models with Auditable + BelongsToOrg, SoftDeletes
- [ ] `LeaveTypeRepositoryInterface` + `LeaveRequestRepositoryInterface` + implementations
- [ ] `LeaveService` (extends BaseService, all writes in `$this->transaction()`)
- [ ] `StoreLeaveRequest` FormRequest with all validation rules
- [ ] `LeaveController` → 4 routes wired
- [ ] Seeder: 6 leave types with Egyptian law citations
- [ ] `countWorkdays` helper excludes weekends + org holidays
- [ ] NULL-manager auto-approve (Decision 13) in `LeaveService::create()`
- [ ] Inertia page `My Leave` with tabs + "New Leave Request" form (useForm, never raw `<form>`)
- [ ] Form shows current balance for selected leave type
- [ ] File upload for medical cert
- [ ] Cancel pending flow
- [ ] Multi-tenant isolation enforced throughout

## Out of scope (this slice)
- HR balance adjustment tool (Feature 9)
- Sick leave layered payment % calculation (Feature 15 Payroll)
- Hourly permissions leave type
- Leave interaction matrix (ANA-3.10) — deferred to Feature 9 compliance workflows

## Compliance citations
```php
// Per Labor Law 14/2025 Art. 89 — annual leave entitlement scales by tenure
// Per Labor Law 14/2025 Art. 54 — sick leave is a right, not discretionary
// Per Labor Law 14/2025 Art. 90 — casual leave 7 days/year, max 2 consecutive
// Per Labor Law 14/2025 Art. 93 (Law 29/2025) — maternity 120 days, female only
// Per Labor Law 14/2025 Art. 93 bis — paternity 1 day per birth, max 3 instances
// Per Labor Law 14/2025 Art. 94 — study leave requires 10-day advance notice
```
