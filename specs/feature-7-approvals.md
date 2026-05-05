# Feature 7: Approvals (Manager / HR view)

## Purpose
Give managers and HR a single queue to review, approve, or reject employee leave requests.

## Routes
| Method | URI | Permission |
|--------|-----|------------|
| GET | `/approvals` | `leave.approve.team` OR `leave.approve.final` |
| POST | `/approvals/{leaveRequest}/approve` | `leave.approve.team` OR `leave.approve.final` |
| POST | `/approvals/{leaveRequest}/reject` | `leave.approve.team` OR `leave.approve.final` |

Middleware: `permission:leave.approve.team|leave.approve.final`

## Who sees what
### Manager (`leave.approve.team`)
Pending requests from direct reports: employees where `employees.manager_id = auth_employee.id` AND employee is not soft-deleted.

### HR (`leave.approve.final`)
All pending requests for the org (full queue). This automatically includes cascaded requests from terminated-manager orphans (ANA-3.18): when a manager is soft-deleted their reports' pending requests are no longer in any manager's queue, so HR is the only approver.

## Approve action
1. Load `LeaveRequest` scoped to `org_id`; `status` must be `pending`
2. Verify authority: manager may only approve their own team; HR may approve any
3. Set `status = 'approved'`, `approved_by_user_id`, `approved_at = now()`
4. Call `LeaveService::adjustBalance($employee, $leaveType, -$days_count)` to decrement balance
5. Wrapped in `$this->transaction()` — Auditable trait captures before/after diff

## Reject action
1. Validate `rejected_reason` — minimum 5 characters (FormRequest)
2. **Decision 19 guard:** if `leaveType->is_right_not_discretion = true` AND user does NOT have `leave.approve.final` (i.e., manager not HR), return 422: "Rights-based leave cannot be rejected by team managers. Escalate to HR."
3. Set `status = 'rejected'`, `rejected_reason`
4. Wrapped in `$this->transaction()`

## ANA-3.18: Approver cascade on terminated approver
No migration required — relies on SoftDeletes. When a manager is soft-deleted their employee records still have `manager_id` set but the manager row is gone. The manager's approval queue naturally empties (query uses `whereHas('employee', fn => $q->where('manager_id', $me))` which excludes requests from employees whose manager is the currently-logged-in-and-still-active manager). HR's queue is un-scoped so it picks them up.

## Decision 13 (NULL manager_id auto-approve)
Already enforced in Feature 6's `LeaveService::create`. No approval action needed here.

## Inertia page: `Approvals/Index`
Props:
```ts
type Props = {
  requests: Paginated<ApprovalRequest>;
  role: 'manager' | 'hr';
};

type ApprovalRequest = {
  id: number;
  start_date: string;
  end_date: string;
  days_count: number;
  status: 'pending';
  reason: string | null;
  leave_type: { id: number; code: string; name: string; is_right_not_discretion: boolean } | null;
  employee: { id: number; name: string; department: string | null };
};
```

UI:
- Header: "B.07 / Leave / Approvals"
- Section 00: `{total} pending request(s)`
- Each row: employee name + department, leave type, dates + day count, reason
- Approve button (gold border, `leave.approve.team` or `leave.approve.final`)
- Reject button (slate border) → expands inline reject-reason textarea → Confirm reject button
- Decision 19 guard: if `is_right_not_discretion = true` AND role = 'manager', disable reject button with tooltip

## N-tier
- **Model:** `LeaveRequest` + `Employee` (existing, no changes)
- **Repository:** add `paginateForManager()`, `paginateForHr()` to `LeaveRequestRepositoryInterface` + `LeaveRequestRepository`
- **Service:** new `ApprovalService` (injects `LeaveRequestRepositoryInterface` + `LeaveService`)
- **FormRequests:** `ApproveLeaveRequest` (no body fields, just authorization), `RejectLeaveRequest` (validates `rejected_reason`)
- **Controller:** `ApprovalController` (index, approve, reject)

## Tests (Pest)
- Guest redirect
- Employee (no `leave.approve.*`) blocked
- Manager sees only their team's pending requests
- Manager does not see other-team requests
- HR sees all pending
- Approve: manager approves own-team request → status approved, balance decremented
- Approve: manager cannot approve non-team request (403)
- Approve: HR can approve any request
- Approve: already-approved request returns 422
- Reject: manager rejects annual leave (discretionary) → status rejected, reason stored
- Reject: manager blocked from rejecting rights-based leave
- Reject: HR can reject rights-based leave
- Reject: reason too short → 422
- ANA-3.18: terminated manager's reports appear in HR queue, not manager queue
