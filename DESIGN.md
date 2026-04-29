# YZH-HR — Design System

> **What this doc is:** the locked design tokens. PRODUCT.md says how the app feels; this says exactly which colors, fonts, sizes, and spacings to use. When in doubt, this is the source of truth.
>
> **Last updated:** 2026-04-29

---

## 1. Color tokens

All exposed as Tailwind utilities via `@theme` in `resources/css/app.css`. Do not introduce off-token colors in components.

### Brand
| Token | Hex | Where to use |
|---|---|---|
| `yzh-gold` | `#d0a946` | Primary action button bg, brand-mark gold, key state highlights |
| `yzh-gold-50` | `#fbf6e6` | Subtle gold-tinted backgrounds (success-like surfaces) |
| `yzh-gold-100` | `#f4e8b8` | — |
| `yzh-gold-200` | `#ecd683` | — |
| `yzh-gold-300` | `#e2c14d` | Hover lighter than base |
| `yzh-gold-400` | `#d8b32f` | Hover state for primary buttons |
| `yzh-gold-500` | `#d0a946` | (alias of `yzh-gold`) |
| `yzh-gold-600` | `#a3852f` | Active/pressed state |
| `yzh-gold-700` | `#76601f` | Reserved |
| `yzh-gold-800` | `#4d3f12` | Reserved |
| `yzh-gold-900` | `#2a220a` | Reserved |

### Dark scale (sidebar, hero surfaces, dark cards)
| Token | Hex | Where to use |
|---|---|---|
| `yzh-ink` | `#121212` | Page bg on dark contexts |
| `yzh-ink-soft` | `#1c1d20` | Cards / sections on dark bg |
| `yzh-ink-mute` | `#33373d` | Borders on dark; subtle dividers |

### Neutrals
| Token | Hex | Where to use |
|---|---|---|
| `yzh-slate` | `#54595f` | Secondary text on light bg; medium-emphasis labels |
| `yzh-text` | `#7a7a7a` | Tertiary / helper text on light bg |
| `yzh-bone` | `#eff0f1` | Soft bg on light contexts; primary text on dark bg |
| `yzh-bone-soft` | `#dee1e7` | Light dividers; secondary text on dark bg |

### Semantic (use Tailwind defaults)
| Intent | Tailwind class | Notes |
|---|---|---|
| Success | `bg-green-600` / `text-green-700` | Approvals, "active" status |
| Warning | `bg-amber-500` / `text-amber-700` | Pending, late check-ins |
| Danger | `bg-red-600` / `text-red-700` | Rejection, deletion confirms only |
| Info | `bg-blue-600` / `text-blue-700` | Neutral notices |

**Rule:** semantic colors are for *system communication*, not branding. Don't make the primary CTA red or green.

---

## 2. Typography

- **Sans:** Figtree (loaded by Breeze, set in `--font-sans` via `@theme`)
- **Mono:** system mono stack for code/IDs (employee codes, audit logs)
- **Weights in use:** 400 (body), 500 (emphasis), 600 (subheadings), 700 (headings only). Never use 800/900.
- **Sizes:** Tailwind defaults. Don't introduce custom sizes — pick the closest scale step.

### Type scale rules
- `text-xs` — labels, captions, table column headers
- `text-sm` — secondary copy, helper text
- `text-base` — body
- `text-lg` — page intros, big numbers in cards
- `text-xl` — section titles
- `text-2xl` — page headings
- `text-3xl` and up — landing/marketing only (Welcome page hero)

### Headings policy
One `<h1>` per page. Subsections use `<h2>` then `<h3>`. Never skip levels.

---

## 3. Spacing & layout

- **Spacing scale:** Tailwind's default 4px-based scale. Use `gap-*`, `space-*`, `p-*`, `m-*` utilities — no arbitrary `[12px]` values.
- **Page max-width:** `max-w-7xl` for HR/admin dashboards (dense). `max-w-2xl` to `max-w-4xl` for employee single-task pages.
- **Padding:** `px-4` mobile, `px-6 lg:px-8` desktop, for all main content containers.
- **Card padding:** `p-4` minimum, `p-6` for hero cards. Never `p-2` on cards (too cramped, an Impeccable anti-pattern).

### Touch targets (mobile)
**Minimum 44×44 px** for any interactive element. That means:
- Buttons: `h-11 min-w-11` (`44px`) at minimum on mobile
- Icon buttons: `h-11 w-11`
- Links in a list: `py-3` minimum

Existing `h-9` / `py-2` patterns from Breeze are OK for desktop-only contexts but must be enlarged on mobile breakpoints.

---

## 4. Borders, radius, elevation

- **Radius:** `rounded-md` (6px) for inputs/buttons. `rounded-lg` (8px) for cards. `rounded-full` for pills/avatars. No `rounded-2xl` or larger — too soft for the brand.
- **Border:** `border-yzh-bone-soft` on light, `border-yzh-ink-mute` on dark. 1px only.
- **Shadow:** `shadow-sm` for resting cards. `shadow-md` for popovers/dropdowns. No heavy `shadow-xl` drops — too floaty for the brand.

---

## 5. Iconography

- **Library:** `lucide-react` only. Don't mix icon libraries.
- **Size:** `h-4 w-4` inline with text, `h-5 w-5` standalone, `h-6 w-6` for empty-state hero icons.
- **Color:** inherit from text (`currentColor`). Don't color-code icons except for status indicators where it carries meaning.

---

## 6. Motion

- **Default duration:** `duration-150` for hover/state transitions, `duration-300` for layout changes.
- **Easing:** `ease-out` (Tailwind default). Avoid bounce/spring — Impeccable anti-pattern; too playful for HR.
- **Respect `prefers-reduced-motion`:** wrap any non-essential motion in `motion-safe:` or `motion-reduce:` variants. Required for a11y.
- **Loading:** prefer skeletons over spinners for any state >200ms.

---

## 7. Forms

- **Field height:** `h-10` (40px) desktop, `h-11` (44px) mobile.
- **Label position:** above the input, `text-sm font-medium`.
- **Error message:** below field, `text-sm text-red-600`. Always present an action ("National ID must be 14 digits").
- **Required indicator:** asterisk `*` after label, `text-red-600`.

---

## 8. Tables (HR-dense screens)

- **Row height:** `h-12` minimum (48px) — comfortable scan-density.
- **Stripes:** off. Use `divide-y` borders only.
- **Hover:** `hover:bg-yzh-bone` row highlight.
- **Sorting:** caret icon `chevron-up` / `chevron-down` (lucide).

---

## 9. Date / time / money formatting (locked)

| Type | Format | Example |
|---|---|---|
| Date | `DD MMM YYYY` | `23 Apr 2026` |
| Time | 12-hour AM/PM | `3:00 PM` |
| Date+time | `DD MMM YYYY · h:mm AM/PM` | `23 Apr 2026 · 3:00 PM` |
| Money | `EGP X,XXX.YY` | `EGP 7,000.00` |
| Phone | `010 XXXX XXXX` (Egyptian) | `010 1234 5678` |
| National ID | `X XXX XXXXXXXXX` (Egyptian, 14 digits) | `2 9803 12345678` |

Centralize formatters in `resources/js/lib/format.ts`. **Never inline.**

---

## 10. Accessibility minimums (WCAG 2.1 AA)

- All text contrast ≥ 4.5:1 against its background
- All interactive elements keyboard-reachable (`tabindex` discipline)
- Focus rings always visible — never `outline-none` without a visible alternative
- Form labels associated with inputs (`<Label htmlFor>`)
- Decorative icons get `aria-hidden="true"`; meaningful ones get `aria-label`
- Skip-to-main link on every page (Phase 2)
