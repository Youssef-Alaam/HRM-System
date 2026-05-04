# Feature 3 — Org Chart

## Status
🟨 In progress

## PRD reference
- PRD §8 Feature 3
- TASK_MANAGER Feature 3 done-when checklist

## User story
Any authenticated user can visit `/org-chart` and see the company's reporting tree: a root for every employee whose `manager_id IS NULL` (CEO + heads of department who report directly to leadership), then their direct reports as children, recursively. Clicking an employee node navigates to that employee's detail page (subject to the existing `employees.view.*` policy on `/employees/{id}`).

This is a read-only visualization; nothing here writes.

## Routes
- `GET /org-chart` — replaces the current placeholder (line wired in F7).

## Data shape

Server returns one tree (or forest of trees) rooted at every NULL-manager employee, with each node carrying the minimal fields the chart needs to render. No client-side joins required.

```ts
type OrgNode = {
    id: number;
    employee_code: string;
    first_name: string;
    last_name: string;
    position: string | null;       // position.title
    department: string | null;     // department.name
    children: OrgNode[];
};

type Props = {
    roots: OrgNode[];   // multiple roots possible (e.g. CEO + cofounders)
};
```

## Edge cases
- **Cycles in `manager_id`** (data error). The service builds the tree with a visited-set guard and skips any node already reachable through another branch — so a cycle becomes two separate sub-trees rather than infinite recursion.
- **Orphan loop** (employee A → manager B → manager A): both surface as roots, the link between them is dropped. Logged via `Log::warning` so a real bad row gets noticed.
- **Inactive employees** (`employment_status != 'active'`) are excluded from the tree but their reports re-parent to the inactive employee's manager (skip-up). Keeps the chart current rather than haunted by terminations.
- **Empty org** (no employees) → empty state with copy "No employees yet."
- **Multi-tenant isolation** — `OrgScope` filters by `auth()->user()->org_id`. Cross-org leakage is the highest-stakes failure here; tested explicitly.

## Acceptance criteria
- [ ] `OrgChartController::index()` exists and is wired at `GET /org-chart`
- [ ] `OrgChartService::buildTree(int $orgId): array` returns the forest payload
- [ ] No new repository — single SELECT through `Employee::query()->with(['position', 'department'])`
- [ ] Inertia page renders the tree via `react-organizational-chart`
- [ ] Each node has: name (bold), `employee_code` (mono), position + department (small text)
- [ ] Click node → `/employees/{id}` (Inertia link, respects existing permission gate)
- [ ] Empty state when there are no roots
- [ ] Mobile responsive — chart container scrolls horizontally on narrow viewports rather than reflowing
- [ ] Inactive employees skipped, their reports re-parent
- [ ] Cycle-safe (visited set in service)
- [ ] Multi-tenant test: org A's chart doesn't leak org B's employees
- [ ] Engineering Studio language: section identifier (`B.02 / Org chart`), drafting-set strip header

## Out of scope (this slice)
- Department-hierarchy mode (parent_department_id rollup) — defer until users ask. The reporting tree is the primary mental model for "org chart".
- Print-friendly view — defer; CSS print styles trivial to add later.
- Pan/zoom controls — react-organizational-chart's default scrolling is enough for v1.

## Compliance citations
- None. Read-only aggregation.
