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
- Services hold all business logic. Extend `App\Services\BaseService` and use its `transaction()` helper for multi-step writes. Never call Eloquent directly.
- Repositories abstract DB access. Extend `App\Repositories\BaseRepository`, declare a contract in `app/Repositories/Contracts/`, and bind it in `AppServiceProvider::REPOSITORY_BINDINGS`. **No business rules.**
- Models = data only. Relationships, scopes, mutators. **No business logic.**

**Reference vertical:** [Holiday CRUD](app/Http/Controllers/HolidayController.php) wires every layer end-to-end (FormRequests, Service, Repository + interface, Resource, route group, container binding, audited writes through `transaction()`). Copy this skeleton when building any new feature; tests in [tests/Feature/Holidays/HolidayLayeredFlowTest.php](tests/Feature/Holidays/HolidayLayeredFlowTest.php) show what end-to-end coverage looks like. Long-form rationale lives in [ARCHITECTURE.md](ARCHITECTURE.md).

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
- Pest 3.8 + `pest-plugin-laravel` installed (F10). Pest builds on PHPUnit, so existing class-style tests continue to work — **write all new tests in Pest function syntax** (`test()`, `it()`, `describe()`). Migrate old PHPUnit files to Pest opportunistically when you're already touching them; no big-bang conversion.
- Reference style: [tests/Feature/Smoke/FoundationSmokeTest.php](tests/Feature/Smoke/FoundationSmokeTest.php). Helpers + global `uses()` live in [tests/Pest.php](tests/Pest.php).
- Run via `php artisan test` or `./vendor/bin/pest`. Both run the full suite.
- Every Service method tested
- Every payroll calculation tested with golden cases
- `php artisan test` passes before commit

## Commits: Conventional Commits
`feat:`, `fix:`, `refactor:`, `docs:`, `chore:`, `test:`, `compliance:`

## Build flow
1. **Foundation Phase (F1-F12.5 in TASK_MANAGER.md):** sequential, no per-task review
2. **F13 Foundation review:** wait for Walid approval
3. **Feature Phase: batched build with 5 named checkpoints** (changed 2026-04-30 per Walid). Build features in spec order; Walid reviews at named moments instead of after every feature. Checkpoints:
   - **Checkpoint A:** after Feature 2 (Employees) — pattern lock validation (~15 min Walid time)
   - **Checkpoint B:** mid-Feature 5 (Attendance) — face-recognition thresholds + selfie retention sign-off
   - **Checkpoint C:** end of Feature Phase 1 (Features 1-8 done) — full sidebar walkthrough (~1 hr)
   - **Checkpoint D:** before Payroll v1 — golden test cases approval (deferred, see below)
   - **Checkpoint E:** after Payroll v1 — math verification (deferred)
4. **Per-feature internal quality gate** (no Walid review) — every feature passes: spec written, Pest tests written first, N-tier built, lint + types + tests + brand-purity + touch-targets + file-size budgets clean, FEATURES_LOG.md entry, demo seed data, CI green.

**Payroll v1 (Feature 15) is DEFERRED** as of 2026-04-30 per Walid: requires bank transfer integrations + accountant input + multiple cross-module connections. Resumes when banking partners + Egyptian payroll-specialist accountant are aligned. All other Feature Phase work proceeds in parallel.

**Build all Feature Phase tasks except Payroll** before resuming Payroll. Reorder: Reports (Feature 16), Compliance Workflows (Feature 17), Government Filings (Feature 18) come before Payroll despite their numbers — they don't depend on it.

**Do NOT skip the 5 named checkpoints.** Each is a hard gate where Walid approval is required before continuing past it.

## Daily handoff email
Walid reads progress on his phone in the morning at the gym. After every meaningful build session, run `php artisan handoff:send {email}` to email a digest (commits, features touched, tests count, demo URLs, what's next). For automated overnight digests, GitHub Actions cron runs at 5:30am Cairo time and sends the same digest. Mail goes through Gmail SMTP via App Password (`MAIL_*` env vars in `.env`).

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
