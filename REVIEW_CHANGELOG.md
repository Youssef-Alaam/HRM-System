# YZH-HR — Review Cycle Changelog

Tracks every change accepted across the 5-cycle SWE + Analyst review.
Final documents will be regenerated from this changelog after Cycle 5.

---

## Cycle 1 — ✅ COMPLETE (35 flags triaged)

### SWE flags — all 15 accepted
(See DECISIONS.md for full details)

### Analyst flags — 17 of 20 accepted, 3 deferred
- ANA-1.19 (expat compliance) — resolved in pre-Cycle-2 decisions (Full Support, Option A)
- ANA-1.15 (self-service scope) — finalized with employee-view screenshots
- Others accepted with Walid's modifications

### 6 pre-Cycle-2 decisions locked
1. Check-in: GPS + selfie + timestamp — UPGRADED IN CYCLE 2 to verdict-based face verification
2. GPS radius: HR configurable, no hard default
3. Selfie retention: 6 months (superseded by Cycle 2: reference photos kept, check-in selfies deleted in 24h)
4. Zoho: Mail + Workplace (SMTP integration only)
5. ClickUp: no integration (stays separate)
6. Expat support: Full support in MVP

---

## Cycle 2 — ✅ COMPLETE (37 flags triaged)

### SWE flags — 17 total

**ACCEPTED AS-IS:**
- SWE-2.1: Payroll expanded to 40-45 days (v1 + v2 split)
- SWE-2.2: Expat module adds ~15 days (distribute across relevant modules)
- SWE-2.4: Dependency graph added to BIG_BUILD_PLAN
- SWE-2.5: seed.ts script produces 30 employees + 3 months history on Day 2
- SWE-2.6: RLS pattern doc in src/lib/supabase/CLAUDE.md; anon+JWT in server actions
- SWE-2.9: "WHEN_STUCK.md" protocol added (/clear → Codex → community → paid help → scope reassess)
- SWE-2.12: Hot-path indexes from Day 2 (attendance, leave_requests, payslips, audit_logs)
- SWE-2.13: Vercel env vars for prod, rotation procedure in OPS.md
- SWE-2.14: Monthly DR drill scheduled (30-min restore test)
- SWE-2.16: Vercel Pro from Day 1 (already in cost estimate)
- SWE-2.17: **UPGRADED** — verdict-based face verification accepted (see below)

**ACCEPTED WITH MODIFICATION:**
- SWE-2.3: PWA confirmed capable of face recognition + GPS, no native app needed. Expanded to 25-28 days.
- SWE-2.7: AI assistant cost revision flagged but **deferred to when feature is built**
- SWE-2.8: Timeline becomes reference-only. Plan restructures around requirements/design/quality.
- SWE-2.10: Doc consolidation to 8-10 files for ease-of-use only.
- SWE-2.11: Claude Code Max marked required (already subscribed by Walid)
- SWE-2.15: Timezone rule is **critical** — remote work means employees in multiple timezones.

### Analyst flags — 20 total

**ACCEPTED AS-IS:**
- ANA-2.1: Acting Manager / delegation_periods feature added
- ANA-2.2: Payroll lifecycle (draft → locked → paid) with retroactive adjustments
- ANA-2.3: Leave balance locked at approval time, not submission time
- ANA-2.4: Attendance request handles forgotten check-ins (self-request within 24h)
- ANA-2.6: Sick leave cert required after 3 consecutive days
- ANA-2.7: Overtime earned on work date, retroactive if approved after payroll lock
- ANA-2.11: Onboarding Workflow — Phase 2 checklist feature
- ANA-2.12: Offboarding Workflow — Phase 2 mirror of onboarding
- ANA-2.13: Negative leave balance per-leave-type configurable
- ANA-2.14: Penalty lifecycle (proposed → applied → disputed → resolved)
- ANA-2.16: Manager Activity Report (Phase 2)
- ANA-2.17: Role conflict enforcement (no user is both manager + HR-approver on same request)
- ANA-2.20: Ramadan banner when Schedule Override active

**ACCEPTED WITH MODIFICATION:**
- ANA-2.5: **Rule confirmed** — Holiday on weekday = normal day off. Holiday on weekend = no replacement.
- ANA-2.9: Keep 3% annual raise at **minimum Egyptian compliance only**.
- ANA-2.10: Birthday/anniversary widget — implementing
- ANA-2.15: Bulk holiday mass-apply feature added for HR
- ANA-2.18: **SIMPLIFIED** — Employees with NULL manager_id auto-approve their own requests.

**REMOVED:**
- ANA-2.8: Peer-to-peer shift swap — **DELETED**.
- ANA-2.19: Multi-currency — **REMOVED**. Earnings in EGP only.

---

## Cycle 3 — ✅ COMPLETE (35 flags triaged)

### SWE flags — all 15 accepted as-is
- SWE-3.1: PAYROLL_TEST_PLAN.md with 30 golden test cases + every-rule unit tests + 3-month parallel run with Mawared
- SWE-3.2: Triple-layer validation (Frontend Zod → Server Action Zod → DB constraints)
- SWE-3.3: ROLLBACK.md with code rollback, DB restore procedure, feature flags, comm templates
- SWE-3.4: P0/P1/P2 alerting with SMS/email/digest channels
- SWE-3.5: Module file structure template, 300-line file size limit
- SWE-3.6: API versioning via service worker check + X-YZH-Version header + schema versioning
- SWE-3.7: Optimistic concurrency (version column) + pessimistic locking for payroll runs
- SWE-3.8: File upload security: MIME whitelist + size limits + magic number verification + signed URLs
- SWE-3.9: Vercel Cron for MVP background jobs, Trigger.dev/Inngest for Phase 2
- SWE-3.10: PDPL self-service data export (ZIP with profile/attendance/leaves/payslips/documents)
- SWE-3.11: Email queue with retry logic + email_failures table + admin retry UI
- SWE-3.12: Migration tooling (dry-run + validation report + transactional rollback + audit trail)
- SWE-3.13: Safe migration pattern in OPS.md (additive only, batched backfill, scheduled in low-traffic windows)
- SWE-3.14: Supabase CLI for local dev parity
- SWE-3.15: k6 load testing before crossing 50 employees, baseline performance documented in OPS.md

### Analyst flags — 16 accepted as-is, 4 modified

**ACCEPTED AS-IS:**
- ANA-3.1: Annual leave entitlement calculated function (15/21/30/45 by tenure+age+disability)
- ANA-3.2: Layered sick leave payment (75%→85%→0%, chronic = 100%)
- ANA-3.3: Casual leave = 7 days/year, max 2 consecutive, deducts from annual
- ANA-3.4: Maternity (120 days, 100%, up to 3 instances) + Paternity (1 day, up to 3) + termination protection
- ANA-3.5: Study leave (paid, doesn't deduct annual, requires 10-day notice + exam proof)
- ANA-3.8: Full report catalog (monthly/quarterly/annual + on-demand)
- ANA-3.9: 3-tier self-edit model (self/HR-approval/admin-only)
- ANA-3.10: Leave interaction matrix in LEAVE_RULES.md
- ANA-3.11: WCAG 2.1 AA accessibility compliance (ACCESSIBILITY.md checklist)
- ANA-3.12: Role change mid-payroll — prorate by days, two line items on payslip
- ANA-3.13: Probation auto-enforcement (3-month max, day-75 alert, day-90 auto-convert)
- ANA-3.14: Deemed resignation auto-detection (10 consec / 20 non-consec) + HR confirm workflow
- ANA-3.15: Configurable leave year-end policy per contract (paid_out / forfeit / rollover_1yr / rollover_2yr)
- ANA-3.17: Retirement workflow (6-mo + 3-mo alerts, age 60 auto-termination unless extended)
- ANA-3.18: Auto-cascade pending approvals when approver terminated (chain → HR fallback)
- ANA-3.19: Minimum wage enforcement (EGP 7,000 default, blocking validation)
- ANA-3.20: Foreign worker quota dashboard widget (green/yellow/red status)

**ACCEPTED WITH MODIFICATION:**
- ANA-3.6: Probation period clarified — 3 months, either side can terminate with no consequences. Post-probation = full law.
- ANA-3.7: **Modified flow** — Default = day off for everyone. HR creates "Holiday Work Request" if needed.
- ANA-3.16: **Modified rule** — Law-minimum balance applied to ALL employees from day 1.

---

## Cycle 4 — ✅ COMPLETE (35 flags triaged)

### SWE flags — all 15 accepted as-is

**Batch 1 (UX foundations):**
- SWE-4.1: Notification matrix (event → in-app/email/push channels) with quiet hours + per-user preferences
- SWE-4.2: i18n architecture from Day 1 with next-intl, English-only content for MVP
- SWE-4.3: Performance budgets per page type (TTI/LCP targets), Lighthouse CI in GitHub Actions
- SWE-4.4: Empty states + error states + form errors + 403/404 design system in DESIGN_SYSTEM.md
- SWE-4.5: Two-layer search (per-page filters + global Cmd/Ctrl+K search across employees/requests/documents/reports)

**Batch 2 (Mobile + realtime):**
- SWE-4.6: PWA push notifications subsystem (VAPID keys, iOS install requirement, service worker pinning)
- SWE-4.7: Realtime updates via Supabase Realtime for critical surfaces
- SWE-4.8: Image optimization pipeline (Sharp, original/optimized/thumb variants, HEIC support)
- SWE-4.9: Background job observability (job_runs table, health dashboard, daily digest email)
- SWE-4.10: Rate limiting on critical endpoints (login 5/15min, password reset 3/hr, check-in 5/min)

**Batch 3 (Security + power UX):**
- SWE-4.11: Idle session timeout (30min admin, 4hr employee mobile) + step-up auth for sensitive actions
- SWE-4.12: 2FA mandatory for Admin/HR roles, optional for Manager/Employee, recovery codes
- SWE-4.13: Date/time picker UX standard (DD MMM YYYY format, 12-hour AM/PM)
- SWE-4.14: Keyboard shortcuts for power users (Cmd+K, J/K navigation, A/R approve/reject, ? overlay)
- SWE-4.15: Centralized export utility (Excel/CSV/PDF, UTF-8 BOM for Arabic, standard filename format)

### Analyst flags — all 20 accepted as-is

**Notifications & Communication:**
- ANA-4.1: Notification quiet hours per user (default 9pm-8am Cairo, employee-configurable)
- ANA-4.2: Email subject line format `[YZH-HR] {action}: {subject}`
- ANA-4.3: HR notification batching (digest instead of 10 separate emails for simultaneous events)

**Onboarding/Offboarding workflows:**
- ANA-4.4: Standard 12-item onboarding checklist template
- ANA-4.5: Standard 10-item offboarding checklist template
- ANA-4.6: Workflow ownership model (HR/IT/Manager/Employee owners, due dates, blocking dependencies)

**Asset module deep-dive (Phase 2):**
- ANA-4.7: Asset lifecycle states (in_storage → assigned → under_repair → scrapped/lost/returned)
- ANA-4.8: One-asset-to-one-employee rule, shared_assets table for multi-user assets
- ANA-4.9: Asset request approval flow (employee requests → manager approves → IT/Admin fulfills)
- ANA-4.10: Termination blocked until all assets returned or written off

**Egyptian government filings:**
- ANA-4.11: NOSI monthly filing — generate compliant CSV export, HR uploads manually
- ANA-4.12: ETA monthly tax filing — same pattern
- ANA-4.13: Annual tax reconciliation auto-generation, mid-January reminder
- ANA-4.14: Form 6 auto-generation per employee at year-end, bilingual, stored permanently

**Mobile-specific UX:**
- ANA-4.15: Pull-to-refresh on all list views
- ANA-4.16: Haptic feedback on key actions
- ANA-4.17: Offline banner ("You're offline. Changes will sync when connected.")

**Disaster recovery:**
- ANA-4.18: Quarterly restore drill (random Saturday, document time-to-restore)
- ANA-4.19: Critical incident playbook (data breach, payroll error, total outage, malicious insider)
- ANA-4.20: Annual mock compliance audit before real audit

---

## Cycle 4 themes
- **Mobile/UX maturity:** notifications, offline support, haptics, push, image optimization
- **Operational readiness:** rate limiting, observability, restore drills, incident playbooks
- **Security depth:** 2FA, step-up auth, idle timeout
- **Compliance specifics:** government filings, annual reconciliation, audit prep
- **Power user productivity:** keyboard shortcuts, global search, export consistency

---

## 2026-04-25 — Architecture pivot (mid-Cycle-4 → pre-Cycle-5)

### Major change: Stack pivot to Laravel + Inertia + React + MySQL + InMotion VPS

**Context:** Walid identified that YZH's existing dev team works in Laravel/PHP and uses InMotion hosting. The original Next.js + Supabase + Vercel plan doesn't leverage team support. Combined with the explicit goal to scale this product to SaaS later, plus the realization that mobile can be Flutter native (Phase 2) instead of PWA (Phase 1), the stack pivots significantly.

### What changed (Decisions 22-25 added)
- **Decision 22:** Tech stack pivot — Laravel + Inertia + React + MySQL + InMotion VPS
- **Decision 23:** Web-first Phase 1, Flutter native mobile Phase 2
- **Decision 24:** SaaS-ready architecture from Day 1 (single-tenant in Phase 1, multi-tenant in Phase 3)
- **Decision 25:** PRD v1.0 scrapped, rewrite for Laravel/Inertia after Cycle 5

### What stays the same
- All 21 prior product/compliance decisions remain valid
- Egyptian labor law requirements unchanged
- All Cycle 1-4 review work stands (142 flags triaged)
- Data model is identical (MySQL syntax differs slightly but structure portable)
- All UX rules, leave rules, holiday rules, face verification approach, termination workflow

### What changes
- BIG_BUILD_PLAN: PWA work (25-28 days) removed from Phase 1; Flutter app added as Phase 2 (~4-6 months future work)
- Cost estimate: roughly EGP 20-30k/year savings vs Vercel + Supabase
- Deployment story: InMotion VPS replaces Vercel; team helps with deployment patterns
- Tech stack section in all docs updated
- PRD v1.0 deprecated (Next.js version invalid)
- Some Cycle 4 SWE flags (4.6 PWA push, 4.7 Supabase Realtime) shift target tech but principle stays

### Affected prior decisions (cleanup)
- **Decision 4 (Zoho integration):** Unchanged — still using Zoho Mail SMTP for emails
- **Decision 7 (Face verification):** Unchanged in concept; implementation uses face-api.js in browser as before
- **Decision 20 (Hosting):** Superseded by Decision 22 — straight to InMotion VPS, no Vercel/Supabase phase

---

## Cycle 5 — 🟡 READY TO START (after Walid's IT conversation + stack confirmation)

Updated focus areas given the pivot:
- Final architecture review on Laravel + Inertia + React + MySQL stack
- Multi-tenancy preparation (Decision 24): which tables, queries, scopes need org_id wiring from Day 1
- Module-by-module implementation order with dependency graph (translated to Laravel patterns)
- Test plan using Pest (Laravel's testing framework) instead of Vitest
- Documentation strategy
- Launch & cutover plan from Mawared
- Post-launch maintenance + support model
- IT audit findings integration
- Last-pass risk register review

## Prototype review cycles (1-2) — [AFTER CYCLE 5]
- Derive PROTOTYPE_PLAN from locked BIG_BUILD_PLAN (now Laravel-flavored)
- Stress-test the prototype scope
