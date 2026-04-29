# YZH-HR — Product & Brand

> **What this doc is:** how the product feels and who it serves. The PRD covers *what* gets built; this covers *how it should feel* when used. Required reading for anyone designing a screen.
>
> **Owner:** Walid. Update when brand voice shifts.
> **Last updated:** 2026-04-29

---

## 1. Who uses YZH-HR

Four roles, with very different expectations:

- **Employees** (~80% of users) — non-technical, often on a phone, opens the app for one specific task: check in, request leave, look at their last payslip. They want speed and certainty, not exploration. Many are not native English speakers; copy must be plain.
- **Managers** — open the app daily to approve their team's leave/attendance corrections. Triage-oriented. Need fast scanning of pending items, low cognitive load on each decision.
- **HR (Sarah's team)** — power users. Live in this app. Expect efficiency, keyboard shortcuts, bulk operations, exports, audit trails. This is their primary tool.
- **Admin** — IT / system owners. Configure offices, holidays, roles. Rare visits, but their actions affect everyone — surfaces must be careful and explicit.

**Implication:** the same app needs both phone-first simplicity (employees) and desktop-density (HR). Don't make HR's screens feel like a phone app, and don't make employees navigate HR-density tables.

---

## 2. Brand voice

YZH Solutions is an Egyptian engineering & contracting firm. The HR system inherits that posture:

- **Confident, not loud.** Like a well-engineered building — solid, considered, no decorative flourishes.
- **Formal but warm.** "Hello, Ahmed" not "Hey 👋". Never cute.
- **Plainspoken English.** No jargon. No HR-speak. Say "you're signed in" not "session active."
- **Egyptian context-aware.** Day/Month/Year dates. EGP. Cairo timezone for company views. Arabic eventually (Phase 2).
- **Compliance-led copy.** When the law is the reason — say so. "Per Egyptian Labor Law Art. 89, your annual leave is 21 days."

**Tone tests for any string:**
- Would Sarah in HR use these exact words to a colleague? → ship.
- Would a US SaaS marketing site use this phrasing? → rewrite.
- Does it apologize, joke, or pad? → cut.

---

## 3. Anti-references — what YZH-HR is NOT

- **Not Mawared HR.** That's the system we're replacing. Visual cluttery, dated, inconsistent. The whole point is to be calmer and faster.
- **Not a US SaaS dashboard.** No purple gradients, no glassmorphism, no marketing-page hero on internal screens, no "Welcome back, you have 3 new things 🎉" energy.
- **Not Notion / Linear / Stripe.** Those are excellent products in their lane (consumer-y, novelty-tolerant). HR is a regulated, sober context.
- **Not a startup.** This is a 19-person Egyptian firm's HR system. Don't dress it up.

---

## 4. Strategic principles

These override component-level decisions when they conflict:

1. **Egyptian Labor Law is the spec.** When law and UX conflict, law wins. Cite the article in the UI when refusing a request.
2. **Soft over hard everywhere.** Soft delete only. Soft validation (warn, then confirm) over outright blocking, except for legal limits. Soft tones over harsh red.
3. **Honest empty states.** "No leave requests yet — submit one from the Leave page" not "Nothing to see here ✨". Tell the user what's true and what to do next.
4. **Audit-trail visibility.** When something was decided by another person (manager approved a leave, HR adjusted a balance), the user sees who and when. No mystery state changes.
5. **Speed > novelty.** Loading state should be a skeleton or a spinner — not a marketing animation. If a page would take >300ms to render, optimistically show the shell.
6. **Mobile-first for employee flows, desktop-first for HR/Admin.** Don't compromise either.

---

## 5. Brand assets — at a glance

(Full design tokens in DESIGN.md.)

- **Logo:** YZH gold monogram on dark, gold/white on light. Never recolored.
- **Primary color:** YZH Gold `#d0a946`. Used sparingly — for primary action buttons, key state highlights, and the brand mark. Not for body text or large fills.
- **Backgrounds:** Near-black ink (`#121212`–`#1c1d20`) on the sidebar / branded surfaces. White or bone (`#eff0f1`) for content.
- **Body type:** Figtree (sans). Plain weights — 400 body, 500–600 emphasis, 700 headings only.
- **Date format:** `23 Apr 2026` everywhere. **Time:** `3:00 PM`. **Money:** `EGP 1,234.56`.

---

## 6. When in doubt

Show the screen to Sarah in HR. If she'd hesitate, change it. If she'd say "yes, this is what I do every day" — ship.
