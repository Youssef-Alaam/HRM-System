# YZH-HR — Daily Scratchpad

> **TASK_MANAGER.md is the master task list.** This file is the daily working scratchpad — the brain dump for THIS session.
>
> **Cleared at end of every session.** Anything important migrates to TASK_MANAGER.md or PROGRESS.md.

**Last session:** 2026-04-29 (late evening — F9 + F10 + F11 + F12 + UI sidebar fixes; deep F12.5 plan written)

---

## 🎯 Today's focus

**F12.5 — Design Pass 1.** Time-boxed 4 hours. Walid mid-checkpoint after Hour 2.

**🚨 READ [F12_5_PLAN.md](F12_5_PLAN.md) FIRST. The whole playbook is there.**

Order (from the plan):
1. **Hour 1:** 6 branded error pages (403/404/419/429/500/503) + exception routing through `bootstrap/app.php` `withExceptions(...)`
2. **Hour 2:** `<Skeleton>` primitive + 2 instance wirings + Welcome polish → **Walid mid-checkpoint**
3. **Hour 3:** 5 Auth pages polish + Dashboard polish + Profile/Edit polish
4. **Hour 4:** Placeholder.tsx polish (lights up 17 routes) + Layouts audit + delete `AuthenticatedLayout.tsx` + final sweep

---

## 🔥 Right now (active task)

Hour 1 — Error pages. Start with reading `bootstrap/app.php` to see the current exception handling shape.

---

## 📋 Next up (this session, after current)

Per F12_5_PLAN.md §4, in order:
1. Skeleton primitive + Welcome polish (Hour 2)
2. **Walid mid-checkpoint** — go/no-go on remaining 2 hours
3. Auth flow + Dashboard + Profile (Hour 3)
4. Placeholder + Layouts + final sweep (Hour 4)
5. **Walid end-checkpoint** — sign-off to unlock F13

---

## 🤔 Questions for Walid

- **Mid-checkpoint after Hour 2:** screenshots of the 6 error pages + skeleton primitive + Welcome → continue full plan / scope-cut / pause for the night?
- **End-checkpoint after Hour 4:** 7-step screen-by-screen walkthrough → green-light F13 / one round of revisions / extend timebox?
- **PRD §3 sanity-read** still queued before F13 — granular permission catalog + per-user override semantics. Not blocking F12.5.

---

## 💡 Notes / discoveries

- **Foundation is fully shipped.** F1 → F12 done, all CI green, branch in sync with origin. F12.5 is pure polish on the visible surface; no infra risk.
- **Inertia persistent layouts pattern is now in use** — Dashboard, Placeholder, Profile/Edit all set `Component.layout = page => <AppLayout>{page}</AppLayout>`. Any new page should follow the same pattern. Sidebar DOM persists across navigations.
- **Holiday CRUD backend exists at `/admin/holidays`** as the n-tier reference vertical. JSON-only — Feature 9 will swap to Inertia + add the React pages. Don't accidentally style this in F12.5; it's not user-visible yet.
- **`AuthenticatedLayout.tsx` is dead code** (Breeze leftover, no imports). Delete in F12.5.
- **`withoutVite()` is in `tests/TestCase::setUp()`** — tests render Inertia views without needing `public/build/manifest.json`. Don't remove this.
- **Audit log writes happen via `Auditable` trait fired by Eloquent events.** Services don't need to dispatch audit explicitly; just wrap multi-step writes in `BaseService::transaction()` so the events commit atomically.
- **Skill routing for F12.5 is locked in F12_5_PLAN.md §2.** Don't freehand. Don't use `industrial-brutalist-ui`, `gpt-taste`, `stitch-design-taste` — they conflict with PRODUCT.md voice.

---

## 🐛 Bugs found

- None open.

---

## ⏸️ Stuck on

- None.

---

## 🚩 Flagged for later (carry over)

Manual checks that have been pending since pre-F9 — all <5 min each, none blocking F12.5:
- **Profile edit walkthrough** (name change, email re-verification, password update, soft-delete account)
- **Multi-tenant smoke** via tinker — create a 2nd org, verify scope isolation between two logged-in users
- **Audit log model-write demo** — create + rename + delete a holiday in tinker, read the before/after diff in `changes`
- **Permission grant/revoke walkthrough** via tinker (UserPermissionService methods + audit-log verification). Real UI lands in Feature 9 Settings → Users & Roles.

These can be folded into the F12.5 final sweep walkthrough (Hour 4), or done as part of F13 Walid review.

---

## ✅ Completed last session

(See PROGRESS.md entry for 2026-04-29 late evening — F9 + F10 + F11 + F12 + UI fixes + F12.5 deep plan. 114 tests passing, CI green, branch in sync with origin.)

---

## 📍 End-of-session checklist

When wrapping up F12.5:
- [ ] Update task statuses in TASK_MANAGER.md (F12.5 → ✅, current task → F13)
- [ ] Migrate "Completed this session" to PROGRESS.md with details (per-screen DoD evidence, Walid checkpoint outcomes)
- [ ] Note any new questions for Walid (especially anything design-token-related that survived the session)
- [ ] Commit work-in-progress with descriptive Conventional Commits messages
- [ ] Push and confirm CI green on the final commit
- [ ] Clear "Right now" and "Next up" sections (set up F13's start)
- [ ] Update "Last session" date at top

---

## 📍 Start-of-session checklist

When starting next session (F12.5):
- [ ] Read this file
- [ ] Read [F12_5_PLAN.md](F12_5_PLAN.md) end-to-end
- [ ] Read [PRODUCT.md](PRODUCT.md) and [DESIGN.md](DESIGN.md) — they are the standards this session is judged against
- [ ] Run `php artisan test` → confirm 114/114 baseline before making any change
- [ ] Run `php artisan serve` + `npm run dev` in background; open http://127.0.0.1:8000
- [ ] Start Hour 1 (Error pages). Don't reorder.
