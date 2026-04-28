# YZH-HR — Decision Log

Every meaningful decision, why we made it, and when.

**Format:** Date — Decision — Alternatives considered — Why this one

---

## 2026-04-23 — Initial decisions (planning phase)

### Scope
- **Prototype in 15 days, not full product.** Alternative was trying to replicate Mawared completely. Rejected: would take 3+ months and fail.
- **Priority is big-plan-first.** Perfect the 18-month full Mawared replacement plan, then derive the 15-day prototype from it as a subset.
- **Internal tool only, not SaaS.** Alternative was multi-tenant from day 1. Rejected: adds 1 week of complexity for no MVP value. Architecture won't paint us into a corner if we change later.

### Tech stack (locked)
- Next.js 15 (App Router) + TypeScript (strict mode)
- Tailwind + shadcn/ui
- PostgreSQL via Supabase (dev + prod environments)
- Drizzle ORM
- Supabase Auth
- Zoho Mail SMTP for email
- @react-pdf/renderer for payslips
- Recharts for charts
- React Hook Form + Zod for forms
- TanStack Query + Server Actions
- Sentry for error monitoring
- Vercel for hosting
- GitHub Actions for CI/CD
- Vitest for unit tests (payroll only in MVP)

### Product
- **English-only MVP.** Arabic + RTL deferred to Phase 2.
- **Gregorian dates only.** Hijri calendar deferred.
- **Payroll Lite in prototype, full payroll in big build.** Full build handles Egyptian tax, SI, health insurance, training fund, annual raise, EOSB.
- **Hardcoded 2-layer approval (Manager → HR).** Configurable approval layers is Phase 2.
- **Dual UI:** Admin/HR/Manager = desktop web layout. Employee = mobile PWA layout.

### Architecture
- **Server Actions over REST API routes** for internal mutations
- **Soft delete (deleted_at timestamp).** Never hard delete HR data
- **Row-Level Security (RLS) from Day 1**
- **Money stored as integers in piasters** (1 EGP = 100 piasters)
- **UTC in DB, Cairo time for display** (date-fns-tz)
- **Audit log for every create/update/delete** from Day 1

### Process
- **Commit every 2 hours.**
- **`/clear` Claude Code between unrelated tasks.**
- **End-of-day ritual: update PROGRESS.md + TODO.md.**

---

## 2026-04-23 — Cycle 1 Review decisions

### SWE flags — all 15 accepted
(See full details in REVIEW_CHANGELOG.md.)

### Analyst flags — 17 of 20 accepted, 3 deferred

### 6 pre-Cycle-2 decisions locked
1. Check-in: GPS + selfie + timestamp — UPGRADED IN CYCLE 2 to verdict-based face verification
2. GPS radius: HR configurable, no hard default
3. Selfie retention: 6 months (superseded by Cycle 2: reference photos kept, check-in selfies deleted in 24h)
4. Zoho: Mail + Workplace (SMTP integration only)
5. ClickUp: no integration (stays separate)
6. Expat support: Full support in MVP

---

## 2026-04-24 — Cycle 2 decisions (Decisions 7-16)

### Decision 7: Face verification — Verdict-based storage (upgraded from Decision 1)
On onboarding, capture a reference photo per employee (encrypted, long-term storage). On every check-in, take a selfie, compare to reference via face-api.js (client-side, free). Store only the verdict: `verified` (>90% match) / `possibly_self` (70-90%, flag for HR review) / `unverified` (<70%, mandatory HR review). Selfies themselves deleted after 24 hours unless flagged.

**Supersedes:** Decision 1 (simple selfie storage) and Decision 3 (6-month selfie retention) — now only reference photos are retained long-term; check-in selfies are ephemeral.

### Decision 8: Project philosophy — Requirements-first, not timeline-first
All timeline estimates in the plan are **reference only**. The project priority order is:
1. Requirements definition (what each feature must do)
2. Design specification (how it works, UX flows, acceptance criteria)
3. Dependency mapping (what blocks what)
4. Quality gates (how we know something is done well)

### Decision 9: Holiday rule — no weekend replacement
Public holiday on a working day = normal day off (paid). Public holiday that falls on a weekend (Friday or Saturday in YZH's default workweek) = no replacement day, no extra off.

### Decision 10: Timezone handling — UTC storage, multi-context display
All timestamps stored in UTC. Display rules:
- **Company views:** display in Africa/Cairo
- **Employee personal views:** display in the employee's local timezone (default Africa/Cairo)
- **Payroll period bucketing:** always Africa/Cairo

### Decision 11: Remove multi-currency from scope
All salaries, allowances, deductions, payslips, and payroll calculations are in EGP only.

### Decision 12: Remove peer-to-peer shift swap
The "Exchange Shifts" and "Exchange Day Off" request types in Mawared are **not replicated**. Employees wanting to swap use the existing "Change Shift" request through their manager.

### Decision 13: CEO / top-of-hierarchy auto-approval
Employees with `manager_id IS NULL` auto-approve their own leave/attendance/other requests. All actions logged. HR receives FYI notification (not approval request).

### Decision 14: Plan restructure for requirements-first focus
BIG_BUILD_PLAN v2 will use feature specifications replacing "days estimated", acceptance criteria per feature, user stories per role, dependency graph, quality gates per module. Timeline information relegated to an appendix.

### Decision 15: Remove ANA-2.8 (peer-to-peer shift swap) — formal removal from scope
Documented removal. Use the existing "Change Shift" request routed through manager instead.

### Decision 16: Remove ANA-2.19 (multi-currency calculations) — formal removal from scope
Payroll operates in EGP only. No conversion logic, no currency field on salary structures.

---

## 2026-04-24 — Cycle 3 decisions (Decisions 17-19)

### Decision 17: Holiday work compensation flow
Public holidays = day off by default for all employees. If HR needs someone to work, they create a Holiday Work Request specifying employee + holiday + hours. Employee not obligated to respond — if they show up and check in, triple pay is automatically applied to next payslip. If they don't show up, no penalty, no record of "refusal," no paperwork. Volunteer check-ins without HR request still get triple pay but flag HR for review.

### Decision 18: Universal law-minimum leave balances
All employees regardless of tenure receive law-minimum leave balance from Day 1:
- Annual leave: 15 days (year 1) / 21 days (year 2+) / 30 days (10+ years OR age 50+) / 45 days (disabled)
- Sick leave: full per Egyptian law
- Permissions: full per Egyptian law
- Casual: 7 days/year
- Maternity: 120 days when applicable
- Paternity: 1 day when applicable, max 3 instances
- Study: as exam dates require

Any contract-granted extras are added manually by HR via the Leave Adjustments tool, with audit trail.

### Decision 19: Leave approval discretion vs. rights model

**Annual leave** — Manager approval discretionary
- Manager can approve OR refuse
- Refusal requires mandatory reason field (logged for audit)
- No tenure-based blocks

**Sick leave / Permissions / Casual / Maternity / Paternity** — Rights, not discretionary
- Approval is essentially confirmation
- Manager can ONLY refuse for: missing required documentation, fraud suspicion (with HR involvement)
- Refusal flagged for HR review automatically

**Study leave** — Manager has approval discretion
- Can be refused for staffing needs
- Mandatory reason field on refusal

---

## 2026-04-24 — Cycle 4 hosting decision

### Decision 20: Hosting strategy — Managed services Phase 0, hybrid optionality
- **Phase 0 (Prototype + early build):** Use Vercel for hosting + Supabase for DB/Auth/Storage.
- **Phase 1 (mid-build, ~6 months in):** Walid conducts an IT audit of YZH's existing server infrastructure.
- **Phase 2 (later, when ready):** Selectively migrate components to company infrastructure if it makes sense.

**Decision rule:** "Migrate when (a) operational team capacity is confirmed, (b) infrastructure assessment is positive, (c) we have downtime budget for migration, (d) the migration delivers clear value."

**Note:** Superseded by Decision 22 (straight to InMotion VPS).

---

## 2026-04-24 — Cycle 4 decision

### Decision 21: Government filings — generate then upload (MVP), API automation later
For NOSI, ETA, and other government filings, MVP scope is to generate compliant data exports in the format required by each portal. HR manually uploads to government portals. API automation is Phase 3 contingent on whether portals offer programmatic submission.

**Affected filings:**
- **NOSI monthly:** social insurance contributions per employee, filed by 15th of next month
- **ETA monthly:** income tax withheld per employee, filed by 25th of next month
- **Annual tax reconciliation:** by January 31, summary of all year's tax for each employee
- **Form 6 (annual):** per-employee annual income/tax breakdown for employees' personal records

---

## 2026-04-25 — Architecture pivot (Decisions 22-25)

### Decision 22: Tech stack pivot — Laravel + Inertia + React + MySQL + InMotion VPS
- **Backend:** Laravel 11 (PHP 8.3+)
- **Frontend:** Inertia.js + React + TypeScript
- **Styling:** Tailwind CSS + shadcn/ui (React components)
- **Database:** MySQL 8 (or MariaDB equivalent)
- **ORM:** Eloquent
- **Auth:** Laravel Sanctum
- **Storage:** Laravel Storage on InMotion VPS filesystem
- **Real-time:** Laravel Reverb (self-hosted, free)
- **Background jobs:** Laravel Horizon + Redis queue
- **Email:** Zoho Mail SMTP (per Decision 4, unchanged)
- **Hosting:** InMotion VPS (NOT shared hosting)
- **Build:** Vite (Inertia default)

**Supersedes:**
- Decision 4 (Zoho integration) — still applies, no change
- Decision 20 (hosting strategy) — managed services Phase 0 is replaced; we go straight to self-hosted on InMotion

**Why:** YZH dev team works in Laravel/PHP. Laravel ecosystem strong in MENA. MySQL skills more transferable. Self-hosted from Day 1. No vendor lock-in. SaaS-ready architecture without Supabase complexity. Lower long-term cost. Inertia keeps single codebase + single deploy. Inertia routes refactor cleanly to API routes for Phase 2 Flutter.

### Decision 23: Web-first, Flutter mobile native app for Phase 2
Phase 1 is web-only. Mobile experience is responsive web. Phase 2 builds a native Flutter mobile app for employees once the web is stable.

**Phase 1 (web only):**
- Desktop-first admin/HR/Manager experience
- Mobile-responsive employee experience
- Geolocation + camera APIs work natively in mobile browsers
- No PWA install flow, no service worker, no offline mode
- No push notifications (employees check email or web)

**Phase 2 (mobile, ~12 months after Phase 1 launch):**
- Flutter native app (iOS + Android single codebase)
- Connects to same Laravel backend via REST API
- Native push notifications via Firebase Cloud Messaging
- Native biometric authentication
- Better offline mode

### Decision 24: SaaS-ready architecture from Day 1 (single-tenant initially)
Code is structured for future multi-tenancy without enabling it in Phase 1:
- Every table has `org_id` (foreign key to organizations table)
- Every query is scoped by `org_id` via Eloquent global scopes
- Auth middleware sets `current_org_id` per request from authenticated user
- Phase 1: only one organization exists (YZH Solutions); `org_id` is effectively constant
- Phase 3 (SaaS): adding new orgs is a config change, not a refactor

### Decision 25: PRD scrap and rewrite for Laravel + Inertia + React
The PRD generated for Next.js + Supabase (PRD.md v1.0) is invalidated by the stack pivot. A new PRD has been written that mirrors the same requirements but uses Laravel + Inertia + React + MySQL + InMotion patterns.

---

## 2026-04-28 — Pre-Cycle-5 additions (Decisions 26-28)

### Decision 26: Backup system — 10-day rolling window + monthly cold backup
- **Daily MySQL dump** at 2 AM Cairo time via Laravel scheduler
- **Rolling 10-day window** stored on local filesystem
- Each backup gzipped, timestamped (`yzh-hr-YYYY-MM-DD.sql.gz`)
- Each backup integrity-verified after creation
- **Pre-deploy backup** runs automatically before every production deploy
- **Monthly cold backup** copied to a separate location for longer retention
- **Quarterly restore drill** (per ANA-4.18)

**Implementation:** Use **Spatie Laravel Backup** package. Laravel scheduler cron entry: `$schedule->command('backup:run')->dailyAt('02:00')`.

### Decision 27: Headcount widget — visual at-a-glance breakdown on dashboard
Prominent dashboard widget for HR/Admin/Manager roles showing real-time workforce status:
- **Hero number:** total active headcount
- **Visual breakdown:** color-coded segments showing percentages of:
  - 🟢 Active on time (checked in within grace period)
  - 🟡 Late (checked in past grace period)
  - 🔴 Absent (scheduled but no check-in)
  - 🔵 On leave (approved leave today)
  - ⚪ Day off (scheduled day off)
- **Visual treatment:** Donut chart (default)
- **Drill-down:** Click any segment → modal with filtered employee list
- **Refresh:** Polling every 60 seconds

### Decision 28: Multi-tenant SaaS readiness — concrete Phase 1 commitments

**Phase 1 implementation commitments:**
- **Every business table has `org_id` column** — non-nullable, foreign key to organizations table
- **Eloquent global scope** (`OrgScope`) automatically filters every query by current `org_id`
- **Auth middleware** sets `current_org_id` per authenticated request via session/JWT
- **All controllers** trust the scope rather than manually filtering (DRY + safer)
- **Tests verify scope works:** if a query somehow bypasses scope, tests fail

**Phase 3 SaaS additions (deferred):** Subdomain routing, tenant signup flow, per-tenant branding, Stripe + Paymob billing, plan tiers, cross-tenant super-admin tools.

**Implementation pattern:**
```php
// app/Models/Concerns/BelongsToOrg.php
trait BelongsToOrg {
    protected static function bootBelongsToOrg() {
        static::addGlobalScope(new OrgScope);
        static::creating(function ($model) {
            if (!$model->org_id) {
                $model->org_id = auth()->user()->org_id;
            }
        });
    }
}
```

---

<!-- Template for new decisions:

## YYYY-MM-DD — [Decision title]
### Decision N: [Short name] — [One-line description]
- **What:**
- **Why:**
- **Alternatives considered:**
- **Revisit when:**

-->
