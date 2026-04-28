# YZH-HR — Code Conventions

**Project:** Internal HR system for YZH Solutions, replacing Mawared HR. Architected for future SaaS commercialization.

**Stack:** Laravel 11 + Inertia.js + React 18 + TypeScript (strict) + MySQL 8 + Tailwind + shadcn/ui

## Read these first
- `PRD.md` — what each feature must do
- `TASK_MANAGER.md` — what to build next + status
- `EGYPT_COMPLIANCE_RULES.md` — legal rules with article citations
- `DECISIONS.md` — why we made choices

## N-tier architecture (mandatory)
Every feature: **Controller → FormRequest → Service → Repository → Model**
- Controllers receive request, validate via FormRequest, delegate to Service. **No business logic.**
- FormRequests handle authorization + validation only.
- Services hold all business logic. Wrap in `DB::transaction` for multi-step. Never call Eloquent directly.
- Repositories abstract DB access. Interfaces in `app/Repositories/Contracts/`. **No business rules.**
- Models = data only. Relationships, scopes, mutators. **No business logic.**

## Multi-tenant from Day 1
Every business model uses `BelongsToOrg` trait. `OrgScope` auto-filters by `auth()->user()->org_id`. Bypass via `withoutGlobalScope` (super-admin only, audit logged).

## Audit log on every write
Every business model uses `Auditable` trait. Captures user, IP, user agent, before/after diff.

## Money + dates
- Money in **piasters** (integer, 1 EGP = 100 piasters). Use `app/Helpers/money.php`.
- Timestamps **UTC** in DB, displayed Cairo (`Africa/Cairo`) for company views, employee-local for personal.
- Dates without time: `DATE` type.
- Date format: `DD MMM YYYY`. Time: 12-hour AM/PM. Money: `EGP 1,234.56`.

## File size limits
- Controllers: 200 lines (extract to Service if longer)
- Services: 150 lines (single responsibility)
- Models: 250 lines
- React components: 300 lines
- Migrations: no limit
- Tests: 500 lines

## Validation discipline
- **FormRequest** for backend (Laravel-native, server-side authoritative)
- **Zod** schema mirroring FormRequest for frontend
- Never trust client-side alone

## Soft delete only. Never hard delete.

## Permission enforcement: Spatie Laravel Permission
- 4 roles: admin, hr, manager, employee
- Per Decision 13: NULL manager_id auto-approves own requests
- Middleware on routes, Policies on models. Both required for sensitive actions.

## Compliance code = cite the law
```php
// Per Labor Law 14/2025 Art. 89 — annual leave entitlement scales by tenure
public function calculateAnnualLeave(Employee $employee): int { ... }
```

## Tests: Pest, before declaring done
- Every Service method tested
- Every payroll calculation tested with golden cases
- `php artisan test` passes before commit

## Commits: Conventional Commits
`feat:`, `fix:`, `refactor:`, `docs:`, `chore:`, `test:`, `compliance:`

## Build flow
1. **Foundation Phase (F1-F12 in TASK_MANAGER.md):** sequential, no per-task review
2. **F13 Foundation review:** wait for Walid approval
3. **Feature Phase:** ONE feature at a time, Walid reviews each, then next

**Do NOT proceed past Foundation Phase F13 without Walid's explicit approval.**
**Do NOT build multiple Feature Phase tasks in parallel.**

## When stuck
1. 2hrs: `/clear` and try fresh prompt
2. 4hrs: try a different AI for fresh angle
3. 6hrs: post in Laravel community
4. 1 day: pay for senior Laravel dev hour ($50-100)
5. 2 days: move to parallel module within same tier, return to blocker later

## Never
- Raw SQL with string interpolation (use Eloquent / parameterized)
- localStorage / sessionStorage in artifacts
- HTML `<form>` tags in React (use Inertia `useForm`)
- `dd()`, `dump()`, `console.log` in committed code
- Skip FormRequest validation
- Skip audit log on writes
- Build features not in PRD without asking
- Hard-code Egyptian compliance values (use Settings + EGYPT_COMPLIANCE_RULES.md as source of truth)
