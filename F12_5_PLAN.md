# F12.5 — Design Pass 1: Deep Plan

> **Read this before opening any code.** This is the operating manual for the F12.5 session. The TASK_MANAGER F12.5 entry is a 6-line summary; this file is the actual playbook.
>
> **Time-box:** 4 hours. Hard limit. Walid mid-checkpoint enforces scope cuts if needed.
> **Owner this session:** Claude Code (with Walid available for review checkpoints).
> **Inputs:** [PRODUCT.md](PRODUCT.md) (how it should feel), [DESIGN.md](DESIGN.md) (locked tokens), [TASK_MANAGER.md](TASK_MANAGER.md) (F12.5 done-when).
> **Out of scope:** every Feature Phase screen (Employees list, Attendance flow, Payroll runs, etc.) — those get designed alongside building each feature.

---

## 1. Operating principles for this session

These override component-level decisions when conflicts arise:

1. **Tokens are LAW.** Every color, radius, shadow, font weight comes from [DESIGN.md](DESIGN.md). If a skill suggests off-token (purple, indigo, glassmorphism, heavy shadows, rounded-2xl) — reject and use the closest token.
2. **PRODUCT.md voice tests every string.** "Would Sarah in HR say this to a colleague? Ship. Would a US SaaS marketing site write this? Rewrite." No emojis. No "Welcome back ✨" energy. No apologies. No padding.
3. **Mobile touch targets ≥ 44×44 px** on every interactive element. Desktop can drop to `h-9` / `h-10` only when `md:` or `lg:` breakpoint is engaged.
4. **Honest empty states.** "No leave requests yet — submit one from the Leave page" not "Nothing to see here." Always: what's true + what to do next.
5. **Soft over hard.** Soft delete only (already enforced backend); soft validation (warn-then-confirm) over outright blocking; soft tones over harsh red except for genuine danger (delete account, terminate employee).
6. **Pattern-match before inventing.** Before designing anything, look at what's already in [resources/js/Components/](resources/js/Components/) and [resources/js/Layouts/](resources/js/Layouts/). Reuse > recreate.
7. **Verify before claiming done.** Each screen passes the screen-level DoD (§5) before moving to the next. Don't batch.
8. **One-h1-per-page.** No exceptions.
9. **Respect `prefers-reduced-motion`.** Wrap non-essential motion in `motion-safe:` variants.
10. **No `dd()`, `console.log`, `dump()` left in committed code** (per CLAUDE.md).

---

## 2. Skill routing matrix

The user explicitly emphasized skills usage. Each kind of work has a designated primary skill. Don't freehand when a skill applies.

| Phase of work | Primary skill | Notes |
|---|---|---|
| Up-front intent check (Welcome + Login first impressions) | `intent-discovery` | One pass at the start to confirm we're building the right first impression for the four user roles in PRODUCT.md §1. |
| Visual reference research for Welcome + Login | `design-research` | Real templates for "internal HR / B2B SaaS / minimalist financial dashboard" — extract layout structure, hierarchy, spacing rhythm. NOT for color palette (locked). |
| Per-screen polish (the workhorse) | `impeccable` | Designed exactly for "design, redesign, polish, audit" of existing UI. Run per screen with current file as input. |
| Upgrade existing pages without breaking | `redesign-existing-projects` | Pair with `impeccable` — `redesign-existing-projects` audits for "generic AI patterns" and applies high-end design without breaking functionality. |
| Skeleton primitive + Error pages (new builds) | `ux-patterns` + `ui-patterns` | Reference proven UX/UI patterns rather than invent. |
| Token / design system enforcement | `ckm-design-system` | Verify tokens-only, no off-token leakage. |
| Brand voice / copy enforcement | (no skill — use PRODUCT.md §2 voice tests inline) | Voice tests are explicit enough that a skill is overkill. |
| Final consistency sweep | `pattern-matching` | Confirms new code mirrors established conventions in the existing codebase. |
| Before declaring any screen done | `verification-before-completion` + `completion-gate` | Run actual verification commands; produce evidence before assertions. |

**Skills NOT to use this session** (wrong taste / scope):
- `industrial-brutalist-ui` — explicitly anti-PRODUCT.md ("not Notion / Linear / Stripe", brutal is wrong direction).
- `stitch-design-taste` — Google Stitch's perpetual-motion aesthetic conflicts with "Confident, not loud."
- `gpt-taste` — GSAP-driven motion + pinning ScrollTriggers — wrong for HR sober context.
- `brandkit` — not building brand identity; brand is locked.
- `ckm-banner-design`, `ckm-slides`, `ckm-brand` — out of scope.
- `imagegen-frontend-mobile` — no mobile-specific image gen needed (mobile = responsive web in Phase 1).

**Skills usable but secondary:**
- `minimalist-ui` — taste profile aligns with PRODUCT.md. Useful as a reference if `impeccable` defaults feel too generic.
- `high-end-visual-design` — can pair with `impeccable` to push past AI-default polish.
- `imagegen-frontend-web` — only if Welcome / Login hero needs visual reference generation. Optional.

---

## 3. Complete inventory of the visible surface

Every page, layout, and primitive that exists today. Anything not on this list either (a) doesn't exist yet (Feature Phase) or (b) is non-visual scaffolding.

### 3.1 Pages currently rendering visible UI

**Pre-login (dark surfaces):**
| File | Purpose | Layout |
|---|---|---|
| `Pages/Welcome.tsx` | Logged-out landing page; brand + 6 feature cards + CTA | None (self-contained) |
| `Pages/Auth/Login.tsx` | Sign-in form | `GuestLayout` |
| `Pages/Auth/ForgotPassword.tsx` | Email entry to request reset link | `GuestLayout` |
| `Pages/Auth/ResetPassword.tsx` | New-password form (from email link) | `GuestLayout` |
| `Pages/Auth/ConfirmPassword.tsx` | Re-confirm password before sensitive action | `GuestLayout` |
| `Pages/Auth/VerifyEmail.tsx` | Resend verification link | `GuestLayout` |

**Post-login (light surfaces):**
| File | Purpose | Layout |
|---|---|---|
| `Pages/Dashboard.tsx` | Welcome card + 4 "Coming up" cards | `AppLayout` (persistent) |
| `Pages/Profile/Edit.tsx` | Account page wrapper | `AppLayout` (persistent) |
| → `Pages/Profile/Partials/UpdateProfileInformationForm.tsx` | Name / email + verify-email banner | (inside Edit) |
| → `Pages/Profile/Partials/UpdatePasswordForm.tsx` | Current + new + confirm password | (inside Edit) |
| → `Pages/Profile/Partials/DeleteUserForm.tsx` | Confirm-password destructive action | (inside Edit) |
| `Pages/Placeholder.tsx` | One template, lights up **17 sidebar routes** | `AppLayout` (persistent) |

**Total: 9 page files + 3 partial files + 1 layout per group = 12 page-level surfaces.**

### 3.2 Layouts

| File | Status |
|---|---|
| `Layouts/AppLayout.tsx` | Active. Polished extensively last session (longest-prefix active match + persistent layout). Audit only this session: density, spacing, focus rings, skeleton hook. |
| `Layouts/GuestLayout.tsx` | Active. Split-screen brand panel (dark) + form column (bone). Polish target: brand panel bottom-anchored "Internal use only" + responsive collapse on mobile (currently `lg:` breakpoint only). |
| `Layouts/AuthenticatedLayout.tsx` | **Unused** (no imports). Breeze leftover. **DELETE in this session**. |

### 3.3 Primitives in `resources/js/Components/`

13 files. Audit each for: token compliance, focus-ring visibility, mobile touch-target, disabled/loading states, ARIA on icon-only buttons.

`ApplicationLogo.tsx`, `Checkbox.tsx`, `DangerButton.tsx`, `Dropdown.tsx`, `InputError.tsx`, `InputLabel.tsx`, `Modal.tsx`, `NavLink.tsx`, `PasswordInput.tsx`, `PrimaryButton.tsx`, `ResponsiveNavLink.tsx`, `SecondaryButton.tsx`, `TextInput.tsx`

Most are Breeze defaults that need brand-token alignment.

### 3.4 New things that get BUILT this session

| Artifact | Where | Why |
|---|---|---|
| `Components/Skeleton.tsx` (+ a few presets like `<SkeletonCard>`, `<SkeletonRow>`) | `resources/js/Components/` | Loading states ≥ 200ms must use skeletons (DESIGN.md §6). Unblocks every Feature Phase screen. |
| Skeleton instances on Dashboard cards + sidebar group during route-transition | `Pages/Dashboard.tsx`, `Layouts/AppLayout.tsx` | Make the skeleton primitive concrete. |
| 6 branded error pages | `Pages/Errors/{403,404,419,429,500,503}.tsx` | Currently bare Symfony defaults. |
| Laravel exception → Inertia render hook | `bootstrap/app.php` `withExceptions(...)` block | Routes the 6 codes through `Inertia::render('Errors/{code}', [...])` so they share `GuestLayout` chrome. Falls back to Symfony for unauthenticated 419 if Inertia not available. |

---

## 4. Order of operations (4-hour timebox)

Strategy: **highest-value-first**. The error pages and skeleton primitive are the biggest user-visible wins and Feature-Phase-unblockers; auth flow and Welcome are first-impression screens; Dashboard / Profile are inside-the-app polish; Placeholder is high-leverage (one fix lights 17 routes).

### Hour 1 — Error pages (60 min)

**Why first:** currently broken-feeling (Symfony defaults). Single biggest visible improvement.

1. (5 min) Read `bootstrap/app.php` to find current exception handling
2. (10 min) Build the exception → Inertia render hook in `withExceptions(...)`. Use Inertia for all 6 codes.
3. (35 min) Build the 6 pages — same `GuestLayout` shell for branding consistency:
   - **403** Forbidden — "You don't have access to this. Contact your HR admin if you think you should." + back-to-dashboard CTA
   - **404** Not Found — "This page doesn't exist (or hasn't been built yet)." + back-to-dashboard CTA
   - **419** Page Expired — "Your session expired. Sign in again." + sign-in CTA. Recovery-friendly.
   - **429** Too Many Requests — "You're going too fast. Try again in a moment." + plain back link
   - **500** Server Error — "Something broke on our side. We've been notified." (we have not — Phase 2 wires Sentry; for now just say it)
   - **503** Maintenance — "YZH HR is briefly down for updates. Back shortly." + estimated-time slot if `Retry-After` header present
4. (10 min) Verification: kick each one in dev (set route to abort(403), etc.), confirm they render through the GuestLayout shell, copy-passes-PRODUCT.md voice tests, mobile + desktop both look right.

**Skill invocations:** `ux-patterns` for error-page conventions; `impeccable` for layout polish; `pattern-matching` to confirm consistency with `GuestLayout` brand panel.

### Hour 2 — Skeleton primitive + Welcome polish (60 min)

**Skeleton (30 min):**
1. (10 min) Build `Components/Skeleton.tsx` — base `<Skeleton className="h-4 w-32 rounded">` with shimmer animation gated on `motion-safe:`. Plus `<SkeletonCard>` (matches Dashboard "Coming up" card shape) and `<SkeletonText lines={n}>`.
2. (15 min) Wire skeletons on Dashboard "Coming up" cards (server prop `loading?: boolean`) + on AppLayout when Inertia's `router.on('start', ...)` fires for nav-triggered loads >200ms.
3. (5 min) Verification: throttle network in DevTools, navigate, confirm skeleton appears, no jank.

**Welcome polish (30 min):**
1. (5 min) Run `intent-discovery` — confirm the four user roles in PRODUCT.md §1 actually need the Welcome page. (Probable verdict: no, this is internal — Welcome is mostly for the "I bookmarked the URL and forgot it's our HR app" moment. Should be brief, not marketing-heavy.)
2. (5 min) Run `design-research` for "internal HR landing page" / "B2B internal tool homepage" — extract 1-2 reference layouts.
3. (15 min) Apply via `impeccable` — likely outcomes: shorter hero, fewer feature cards (drop Phase 2 stuff like "Multi-tenant ready" — wrong audience), tighter typographic rhythm, footer simplification.
4. (5 min) Verify in browser — both logged-in and logged-out states, mobile + desktop.

**Skill invocations:** `intent-discovery`, `design-research` (one pass), `impeccable`, `pattern-matching`.

### Hour 3 — Auth flow polish + Dashboard + Profile (60 min)

**Auth flow polish (30 min):**
1. Login.tsx (10 min) — already in good shape, audit only. Possible wins: "Sign in" button loading-text width-stable; `caps-lock` warning on password field; remember-me copy reduction.
2. ForgotPassword.tsx + ResetPassword.tsx + ConfirmPassword.tsx + VerifyEmail.tsx (20 min total) — read each, apply `impeccable` for consistency with Login. Same heading hierarchy, same button labels, same error styles.

**Dashboard polish (15 min):**
1. Apply `impeccable` to Dashboard.tsx + DashboardHeader.
2. Likely wins: "Coming up" card hover-states (lighter touch — currently border color), date format from DESIGN.md §9 (`23 Apr 2026`), greeting time-aware ("Good morning, Admin." instead of always "Hello,") if PRODUCT.md voice allows (§2 says "Hello, Ahmed" — keep "Hello,").
3. Wire skeleton state from Hour 2.

**Profile/Edit polish (15 min):**
1. Apply `impeccable` to Edit.tsx + 3 partials.
2. Likely wins: section dividers, danger-zone visual treatment (already red-tinted, audit if appropriate), email-verify banner copy, password-strength indicator (skip if scope-cut needed).

**Skill invocations:** `impeccable` per file, `pattern-matching` after each.

### Hour 4 — Placeholder + Layouts audit + Final sweep (60 min)

**Placeholder.tsx (15 min):**
1. Apply `impeccable`. Highest leverage of the session — one fix lights 17 routes.
2. Likely wins: real "Next:" treatment (chips? list?), "Coming soon" softer treatment, contextual CTA per page (link to relevant doc / settings if any), spacing rhythm.

**Layouts audit (15 min):**
1. AppLayout.tsx — density audit, focus rings, sidebar collapse animation timing. Don't redesign — just sand the rough edges.
2. GuestLayout.tsx — mobile responsive collapse, brand panel bottom-anchor consistency.
3. **Delete `AuthenticatedLayout.tsx`** (unused, confirmed via grep).

**Final sweep (30 min) — full app walkthrough as Walid would do it:**
1. Logged-out walkthrough: Welcome → Sign in → Forgot password (don't actually submit) → back to Login → submit valid creds.
2. Logged-in walkthrough as `admin@yzh.test`: Dashboard → click every sidebar item → Profile → user menu → Admin section → System Settings → log out.
3. Mobile breakpoint walkthrough: shrink browser to 375px, repeat the above.
4. Force-trigger each error page: `route('debug-403', fn() => abort(403))` etc., or just visit a non-existent URL for 404, log out and try a protected page for 419.
5. Brand-purity scan via grep — no `purple-`, `indigo-`, `blue-` (except semantic info), no off-token hex codes anywhere in `resources/`.
6. Touch-target scan — every `<button>`, every `<a>` that's clickable, confirm `h-11`/`min-h-11` on mobile (`lg:h-9` permitted).
7. Run `php artisan test` — must still be 114/114.
8. Apply `verification-before-completion` skill — every claim of "done" needs evidence (a command or a screenshot).

**Skill invocations:** `impeccable`, `pattern-matching`, `verification-before-completion`, `completion-gate`.

---

## 5. Definition of done — per screen

Each screen is "done" only when ALL of these pass. No batching.

### Visual
- [ ] Tokens-only colors (no off-token hex, no `purple-*` / `indigo-*` / Laravel-blue)
- [ ] Gold (`yzh-gold`) used only for primary action + brand mark — NOT body text, NOT decorative fills
- [ ] Type scale matches DESIGN.md §2 — no custom sizes
- [ ] Spacing on Tailwind 4px scale — no `[12px]` arbitrary values
- [ ] Card padding ≥ `p-4`; hero cards `p-6`
- [ ] Radius matches DESIGN.md §4 (`rounded-md` inputs/buttons, `rounded-lg` cards, `rounded-full` pills)
- [ ] Shadow `shadow-sm` resting / `shadow-md` popovers — no `shadow-xl`+

### Voice
- [ ] Every string passes PRODUCT.md §2 voice tests (Sarah-test, US-SaaS-test, apologize-joke-pad-test)
- [ ] No emojis
- [ ] Plainspoken English; no jargon; no HR-speak
- [ ] Empty states tell user what's true + what to do next

### Accessibility
- [ ] One `<h1>` per page, no skipped heading levels
- [ ] All interactive elements ≥ 44×44 px on mobile (`h-11`/`min-h-11`)
- [ ] Focus ring visible on all focusable elements (`focus-visible:ring-2 focus-visible:ring-yzh-gold`)
- [ ] Form labels associated via `htmlFor`
- [ ] Decorative icons `aria-hidden="true"`; meaningful icons `aria-label`
- [ ] Text contrast ≥ 4.5:1 (visual check against light + dark surfaces)
- [ ] `motion-safe:` / `motion-reduce:` variants on any non-essential motion

### Behavioral
- [ ] Loading states use `<Skeleton>` for >200ms, not spinner
- [ ] Disabled buttons retain visible label (no opacity-to-zero)
- [ ] Error states present an action ("National ID must be 14 digits") not just "Invalid"
- [ ] Inertia `useForm` for all forms; no `<form action="POST">` HTML

### Responsive
- [ ] Renders at 375px (iPhone SE), 768px (iPad), 1024px (laptop), 1440px (desktop) without overflow or wrap-into-soup
- [ ] Touch-friendly on mobile, dense on desktop where appropriate

### Code hygiene
- [ ] No `console.log`, `dd()`, `dump()` in committed code
- [ ] File ≤ 300 lines (CLAUDE.md React budget); extract sub-component if larger
- [ ] No new component invents what `Components/` already has — reuse > recreate
- [ ] TypeScript strict — `npx tsc --noEmit` passes

---

## 6. Walid review checkpoints

Two formal checkpoints. Don't skip either.

### Mid-checkpoint (after Hour 2)
**Walid reviews:** error pages live, skeleton primitive working, Welcome polished.
**Walid decides:** continue full plan / scope-cut to high-priority screens only / pause here for the night.
**Format:** Claude posts screenshots + a 5-bullet what-changed summary. Walid responds within the session OR asynchronously.

### End-checkpoint (after Hour 4)
**Walid reviews:** full app walkthrough using the path in §4-Hour 4-Final-sweep.
**Walid decides:** F12.5 done → unlocks F13 / one round of revisions before locking / extend timebox by Xh.
**Format:** Claude posts the walkthrough as a 7-step screen-by-screen with notes per step. Walid green-lights to proceed to F13 OR sends specific revisions.

---

## 7. Anti-patterns to actively avoid

A short list of things the design skills tend to suggest that conflict with PRODUCT.md / DESIGN.md. Reject if a skill output includes any of these:

- Glassmorphism, neumorphism (DESIGN.md §4 implicitly forbids; PRODUCT.md §3 explicitly anti-Notion-Linear-Stripe)
- Gradient backgrounds (especially purple→pink → US-SaaS energy)
- Bouncy / spring motion (DESIGN.md §6 explicitly bans)
- Marketing-page heroes on internal screens (PRODUCT.md §3)
- Decorative icons on every section header
- Welcome-back-with-emoji or any "you have N new things ✨" energy
- Border-radius `rounded-2xl` or larger (DESIGN.md §4 explicitly bans — "too soft for the brand")
- `shadow-xl` / `shadow-2xl` (DESIGN.md §4 — "too floaty")
- Off-brand color fills (Laravel default purple/indigo, blue gradients, Tailwind pink)
- Variable font weights 800/900 (DESIGN.md §2 caps at 700)
- Toast notifications styled like consumer apps (we've not built notifications yet — defer)
- Mock data in components (every screen reads from `usePage().props` or seeded backend)
- Inline SVGs when `lucide-react` has the icon (DESIGN.md §5)

---

## 8. Files to NOT touch this session

These are stable or out of scope. Touching them risks regression for zero F12.5 value:

- `app/**` — backend layers (already locked by F9 n-tier scaffolding)
- `database/**` — migrations, seeders (locked)
- `tests/**` — except adding new tests for error pages if exception handler needs coverage
- `routes/web.php` — except registering error-page routes if the exception hook needs them
- `config/**` — locked
- `bootstrap/app.php` — touch ONLY for `withExceptions(...)` block, nothing else
- `resources/css/app.css` — only if a token needs adjustment, and only after Walid sign-off on the change
- `tailwind.config.js` — same as above

---

## 9. Risk register

Things that could blow the 4h timebox:

| Risk | Mitigation |
|---|---|
| Error-page exception routing turns out tricky in Laravel 11 (new `bootstrap/app.php` style) | Time-box to 60 min. If hour 1 runs over, scope-cut to 4 codes (404, 419, 500, 503) and defer 403/429 to a follow-up. |
| Skeleton instance wiring requires AppLayout refactor | The persistent-layout refactor is already done (last session). Skeleton hook should be additive. If not, defer instance wiring to Feature Phase, ship just the primitive. |
| `impeccable` skill suggests too many screens to redesign deeply | Use the §5 DoD as a stopping criterion per screen — "polished" not "perfect". |
| Welcome page intent-discovery suggests deletion entirely | Acceptable outcome. If Walid agrees, delete Welcome.tsx and route `/` to login when logged out, dashboard when logged in. Ship as a separate commit. |
| Mobile responsive bugs surface late | `Final sweep` step has explicit 375px / 768px walkthrough. If found late, log to TODO.md and fix in F13 prep. |
| Tests fail after Skeleton + AppLayout wiring | Run `php artisan test` after each Hour, not only at end. Catch regressions early. |

---

## 10. Session-end deliverables

Before closing the F12.5 session, produce:

1. **All 9 page files + 3 partials + 2 layouts** polished per §5 DoD
2. **6 error pages** + exception routing wired
3. **Skeleton primitive** + at least 2 instance wirings (Dashboard cards, AppLayout route transition)
4. **AuthenticatedLayout.tsx deleted**
5. **TASK_MANAGER.md** F12.5 marked ✅ with the side-fixes section listing what got changed beyond plan
6. **PROGRESS.md** entry for the session
7. **Commit(s)** following Conventional Commits — likely 4-6 commits (error pages / skeleton / auth polish / dashboard+profile / placeholder+layouts / final sweep)
8. **CI green** on the final push
9. **Walid sign-off** in the session OR explicit "review pending" note in TASK_MANAGER

---

## 11. The next session's first 5 actions

When the next Claude opens with `/clear` and reads CLAUDE.md → MEMORY → TASK_MANAGER → this file:

1. Read this entire file (you're doing it now).
2. Read [PRODUCT.md](PRODUCT.md) and [DESIGN.md](DESIGN.md) — they are the standards this session is judged against.
3. Run `php artisan test` → confirm 114/114 baseline before making any change.
4. Run `php artisan serve` + `npm run dev` in background; open http://127.0.0.1:8000 in a browser.
5. Start Hour 1 (Error pages). Don't reorder.
