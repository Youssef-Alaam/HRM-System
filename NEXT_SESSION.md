# Next session — pickup doc

> **For the AI in the next chat. Read this first, in this exact order, before doing anything.**

## Read order (5 minutes max)

1. **This file** — snapshot of where things stand + clear next action
2. **[CLAUDE.md](CLAUDE.md)** — project conventions, build flow with 5 checkpoints, payroll deferral, daily digest pattern
3. **[TASK_MANAGER.md](TASK_MANAGER.md) §"Current status" + the new "Phase 1 build queue" block** — locked work items in order
4. **The four Phase 1 specs:**
   - [specs/feature-2-employees.md](specs/feature-2-employees.md) — read the original spec PLUS the "Revisions locked 2026-04-30" section at the bottom
   - [specs/feature-11-documents.md](specs/feature-11-documents.md) — full spec
   - [specs/feature-22-assets.md](specs/feature-22-assets.md) — full spec
   - [specs/face-enrollment.md](specs/face-enrollment.md) — full spec
5. **[FEATURES_LOG.md](FEATURES_LOG.md)** — Feature 1 done, Feature 2 partial. Append new entries as features ship.

Don't re-derive design decisions. They're locked. Just build.

## Current state of the repo

- **Branch:** `claude/yzh-hr-prototype-prd-xYh5w`, **17 commits ahead of origin** (Walid hasn't authorized push)
- **Tests:** 144 passing, 669 assertions
- **TypeScript strict + ESLint + brand-purity grep + em-dash sweep:** all clean
- **Phase:** Feature Phase 1 (F12.5 closed, F13 waived by Walid)
- **What's running:** Gmail SMTP digest works (verified end-to-end); Compass Arc loading screen wired across in-app nav; Welcome → Login morph via View Transitions API; Engineering Studio design language across every surface

## What ships next, in this order

The 9-item queue is locked in TASK_MANAGER.md. Sequenced for dependencies:

| # | Item | Effort | Spec |
|---|---|---|---|
| 1 | Search bar UX fix (AppLayout same-pathname skip + scoped page loading) | 10 min | feature-2 §"Revisions" |
| 2 | EMP-XXXXX migration + backfill 28 employees + position type_code | 1-2 hr | feature-2 §"Revisions" |
| 3 | Sidebar restructure (Departments/Positions/Offices → Settings; Documents + Assets to top-level) | 30 min | feature-2 §"Revisions" |
| 4 | Employee create form + Tier 1 self-edit on Profile | 45 min | feature-2 main spec + §"Revisions" |
| 5 | **Documents feature (was Feature 11)** | ~1 day | feature-11 |
| 6 | **Assets feature (was Feature 22)** | ~1 day | feature-22 |
| 7 | **Face enrollment flow** (subset of Feature 5 Attendance, lands now) | ~1 day | face-enrollment |
| 8 | Realistic seed overhaul (medium depth — 95% laptop, 60% phone, 30% accessories, varied doc compliance) | 2 hr | feature-22 §"Realistic seed" + feature-11 |
| 9 | Export buttons (CSV + Excel via maatwebsite/excel) on Employees / Assets / Documents | 3 hr total | feature-2 §"Revisions" |

**Total:** ~5-6 days of focused work. After this queue clears, Feature 3 (Org Chart) is next per TASK_MANAGER's Feature Phase order.

## Key conventions that don't change

These come from CLAUDE.md but are repeated here so the next AI doesn't miss them:

- **N-tier mandatory:** every feature → Controller → FormRequest → Service → Repository → Model. Holiday CRUD is the reference vertical. Dashboard (Feature 1) and Employees (Feature 2) follow this pattern.
- **Tests first.** Pest function syntax for new tests. Helpers in `tests/Pest.php`. Run `php artisan test` before committing.
- **Money in piasters** (integer, 1 EGP = 100 piasters). Use `lib/format.ts`'s `formatMoneyEgp()`.
- **Dates:** `DD MMM YYYY` user-facing (use `formatDate()`). UTC in DB; Cairo for company views.
- **Multi-tenancy:** every business model uses `BelongsToOrg` trait. `OrgScope` auto-filters. Tests verify isolation.
- **Auditable:** every business model uses `Auditable` trait. Tests verify audit_logs entries on writes.
- **Soft delete only.** Never `forceDelete()`.
- **No card grids** (impeccable absolute ban). No pill+dot kicker shapes. No em-dashes in user-facing copy. No rounded-2xl. No glassmorphism.
- **Engineering Studio design language:** title-block strips, drafting-set notation (kicker `B.01 / People`), monospace metadata, gold-as-architectural-annotation, period on declarative titles, thin keyline dividers (no white card boxes for sections).
- **Stamped CTA pattern** (border + uppercase mono + `ArrowUpRight` lucide + hover-fill) on guest-layer surfaces. Inline classes today; will extract to `<StampedButton>` primitive when an 11th use lands.
- **Per-feature internal quality gate** before commit: TypeScript strict + ESLint + Pest green + brand-purity grep + em-dash sweep + file-size budgets + FEATURES_LOG.md entry.

## Per-session ritual

At the END of every meaningful build session, run:

```bash
# Update test count for the digest
php artisan test 2>&1 | grep "Tests:" > storage/app/last-test-count.txt

# Send the digest
php artisan handoff:send
```

The digest goes to `yssfallam@gmail.com`. Walid reads it on his phone in the morning at the gym. **Every commit-worthy session ends with a digest.**

## Walid's preferences (from session 2026-04-30)

- **No per-feature checkpoints.** F13 was waived. Build the whole queue, send digests, Walid reviews bulk at the end.
- **Build all features except Payroll** (Feature 15 deferred until banking + accountant ready).
- **HR uploads docs, not employees.** Anything in the Documents system is implicitly verified.
- **Manager only sees own assets**, not their team's. Unusual but explicit.
- **Egyptian context first.** Sun-Thu workweek. Egyptian National ID 14-digit. EGP. Cairo timezone.
- **Documents needed list (Egyptian) is in the Feature 11 spec** — copied straight from his HR.
- **Sidebar must stay tight.** Configuration goes in Settings (top-right user dropdown), not in sidebar. He pushed back on having Departments/Positions/Offices in the sidebar.
- **Realistic seeded data so he can visualize.** Don't seed empty rows.

## Quick verification commands

Before starting work, run these to confirm the repo state:

```bash
# Repo clean?
git status

# Tests still green?
php artisan test 2>&1 | tail -3

# TypeScript strict?
npx tsc --noEmit

# Dev servers running? (if not, start them)
php artisan serve --port=8000        # in one terminal, keep open
npm run dev                          # in another, keep open
```

If any of those fails, that's the FIRST thing to fix.

## What NOT to do

- **Don't push to origin.** Walid's rule: "no git push without permission." 17 commits are sitting locally. Wait for explicit `"push"` from Walid.
- **Don't change CLAUDE.md, PRD.md, DECISIONS.md, EGYPT_COMPLIANCE_RULES.md, PRODUCT.md, DESIGN.md** unless explicitly asked. They're load-bearing source-of-truth docs.
- **Don't rewrite shipped features.** Feature 1 Dashboard + Engineering Studio language are locked. Don't redesign.
- **Don't introduce new design patterns.** The Engineering Studio language is the standard. Match it.
- **Don't enable per-feature checkpoints.** Walid waived them. Just build.

## First action in the new chat

1. Read this doc + CLAUDE.md + TASK_MANAGER.md current-status block + the four spec files (5 min)
2. Run the verification commands above
3. Send a "starting" digest with `php artisan handoff:send` so Walid sees the new session opened
4. Start with **Item #1 — Search bar UX fix** (10 min, easy warm-up)
5. Move into **Item #2 — EMP-XXXXX migration** when #1 ships

Then continue down the queue. End of session → final digest. Walid reads in the morning.
