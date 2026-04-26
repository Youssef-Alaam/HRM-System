# YZH-HR

Internal HR system for YZH Solutions. Replacing Mawared HR over multiple stages.
This repo currently holds the **prototype** (per the project PRD v1.0).

## Stack

- **Next.js 16** (App Router) + TypeScript + Tailwind v4
- **MySQL** via Drizzle ORM (replaces the PRD's Postgres+Supabase plan — see "Stack
  notes" below)
- **NextAuth v5** (credentials provider, bcrypt) for auth
- **shadcn/ui-style** components (vendored locally)
- **TanStack Query** for client data, Server Actions for mutations
- **Zod** for validation, both ends

## Quick start

```bash
# 1. Install dependencies
pnpm install

# 2. Configure env
cp .env.example .env.local
# Fill in DATABASE_URL (your local MySQL) and AUTH_SECRET (any 32-byte random string)
# Generate AUTH_SECRET: openssl rand -base64 32

# 3. Create the database (in MySQL CLI or your client of choice)
#    CREATE DATABASE yzh_hr CHARACTER SET utf8mb4;

# 4. Push schema to the database
pnpm db:push

# 5. Seed demo data (8 employees, ~3 months attendance, sample leave requests)
pnpm seed

# 6. Run the app
pnpm dev
```

Then visit <http://localhost:3000>.

### Test accounts

All accounts share password **`Password123!`**.

| Role     | Email                  |
|----------|------------------------|
| Admin    | `admin@yzh.test`       |
| HR       | `hr@yzh.test`          |
| Manager  | `manager@yzh.test`     |
| Employee | `alex@yzh.test`        |

Other employees: `blake@yzh.test`, `casey@yzh.test`, `drew@yzh.test`,
`design.lead@yzh.test`.

## Scripts

| Command              | What it does                                       |
|----------------------|----------------------------------------------------|
| `pnpm dev`           | Run Next.js dev server                             |
| `pnpm build`         | Production build                                   |
| `pnpm start`         | Run production build                               |
| `pnpm lint`          | ESLint                                             |
| `pnpm db:push`       | Push schema to MySQL (no migration files)          |
| `pnpm db:generate`   | Generate migration files from schema diff          |
| `pnpm db:migrate`    | Apply pending migrations                           |
| `pnpm db:studio`     | Drizzle Studio (DB browser)                        |
| `pnpm seed`          | Wipe + re-seed demo data                           |

## Project structure

```
src/
  app/
    (auth)/login/         Public login page
    (app)/                Protected routes (sidebar/topbar layout)
      page.tsx            Role-aware dashboard router
      _dashboards/        Per-role dashboards
      employees/          List, detail, create, edit
      attendance/         Stub for next pass
      leave/              Stub for next pass
      schedule/           Stub for next pass
      org-chart/          Working tree view
      settings/           Departments, positions, offices, holidays, users, audit log
      coming-soon/[m]/    Placeholder pages for v2 modules
    api/auth/[...nextauth]  NextAuth handlers
    layout.tsx
    not-found.tsx
  components/
    ui/                   shadcn-style primitives
    layout/               Sidebar, Topbar
    page-header.tsx, stat-card.tsx, coming-soon-card.tsx, stub-page.tsx
  db/
    schema.ts             All Drizzle tables (prototype + big-build placeholders)
    index.ts              MySQL pool + Drizzle client
  lib/
    auth.ts               NextAuth config (credentials + bcrypt + lockout)
    rbac.ts               Permission helpers (replaces Supabase RLS)
    audit.ts              Audit log writer
    env.ts                Env var validation (Zod)
    nav.ts                Sidebar nav config
    utils.ts              Date / money / cn / initials
    actions/              Server Actions (create/update/delete employee, etc.)
    validation/           Zod schemas
  middleware.ts           Auth gate for protected routes
scripts/seed.ts           Seed script (pnpm seed)
drizzle.config.ts         Drizzle Kit config
```

## Stack notes — deviation from the PRD

The PRD locks **Postgres + Supabase** (Section 3) and relies on Supabase Auth,
Storage, and RLS. We're using **MySQL** (Walid's call) which means:

- **Auth**: NextAuth v5 with credentials provider + bcrypt (in `src/lib/auth.ts`).
- **RLS replacement**: All Server Actions and protected route handlers go through
  `requireUser` / `requireRole` / `canViewEmployee` etc. in `src/lib/rbac.ts`.
  The DB has no row-level security — the gate is at the action boundary.
- **Storage**: Selfies / profile photos will write to `./public/uploads` for the
  prototype. Swap to S3/R2 by implementing a tiny adapter when going to prod.

Also: scaffolded with **Next.js 16** and **Tailwind v4** (latest at scaffold time)
rather than the PRD's Next 15 / Tailwind 3. App Router architecture is identical.

## What's implemented (prototype acceptance criteria)

| #  | Criterion (PRD §8)                                                  | Status              |
|----|---------------------------------------------------------------------|---------------------|
| 1  | Login works for at least 4 test accounts                            | ✅ Done              |
| 2  | Each role sees their appropriate dashboard                          | ✅ Done              |
| 3  | HR can create, view, edit an employee                               | ✅ Done              |
| 4  | Employee can see their own profile                                  | ✅ Done (`/employees/me`) |
| 5  | RBAC actually works                                                 | ✅ Done (rbac helpers + permission matrix) |
| 6  | Employee can check in (selfie + GPS)                                | ⏳ Stub — next pass  |
| 7  | Attendance history displays correctly                               | ⏳ Stub — next pass  |
| 8  | Employee can submit a leave request                                 | ⏳ Stub — next pass  |
| 9  | Manager sees the request in their inbox, can approve                | ⏳ Stub — next pass  |
| 10 | HR sees manager-approved request, can finalize                      | ⏳ Stub — next pass  |
| 11 | Employee sees status update + balance decrement                     | ⏳ Stub — next pass  |
| 12 | Settings: HR creates departments/positions/offices/holidays         | 🟡 Read-only listings, write UI next pass |
| 13 | Audit log shows every write action                                  | ✅ Done              |
| 14 | All "Coming Soon" placeholders visible                              | ✅ Done              |
| 15 | Mobile responsive                                                   | ✅ Layout responsive; check-in flow not built yet |
| 16 | Deployed to Vercel                                                  | ❌ Pending Walid     |
| 17 | 5 test employees seeded with 3 months attendance                    | ✅ 8 employees + ~90 days attendance |

## What's next

In rough order:

1. **Attendance check-in/out flow** — Geolocation + selfie capture + Server Action
   to write `attendance_records`, with Haversine radius check.
2. **Leave: request + approval** — full forms, manager + HR approval Server
   Actions, balance decrement, calendar view.
3. **Settings write UI** — create/edit/delete for departments, positions, offices,
   holidays.
4. **Vercel deploy** — once a hosted MySQL connection string is available.
