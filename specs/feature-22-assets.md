# Feature 22 — Assets

## Status
⬜ Not started — promoted from Phase 2 to Phase 1 per Walid 2026-04-30.

## PRD reference
- TASK_MANAGER Feature 22 ("Asset module") — moved up from Phase 2
- Egyptian Labor Law records-keeping for company property

## What this feature does

Track company-owned equipment (laptop / phone / accessories / badge ID / etc.) assigned to employees. HR + Admin manage the inventory; employees see only their own currently-assigned items; managers see only their own (not their team's, per Walid's explicit instruction).

## Locked decisions

- **Assignment authority:** HR + Admin only. Employees and Managers cannot assign / unassign.
- **Visibility:**
  - **Employee** → own currently-assigned assets only. No history.
  - **Manager** → own currently-assigned assets only. **Not** their team's. (Walid's explicit instruction 2026-04-30. Unusual but firm.)
  - **HR + Admin** → all assets across the org + full chain-of-custody history.
- **Value tracking:** HR/Admin enters value at assignment time, in **piasters** (per Decision 8). No depreciation calculations.
- **Age tracking:** **Option B (snapshot)** per Walid 2026-04-30. HR enters `age_at_assignment_months` as a number. Stays static until reassignment, then re-entered by HR.
- **No vehicle-specific fields.** YZH doesn't currently issue vehicles. Generic asset model only.
- **No reservation pool.** All assets are 1:1 assigned to a single employee at a time.
- **Custom categories extensible.** Starter list: `Laptop`, `Accessories`, `Phone`, `Badge ID`. HR can add categories from Settings (e.g., when YZH starts issuing safety helmets later).

## Data model

### `asset_categories` table

```
id, org_id, name, description (nullable), icon_name (lucide-react name, default 'package'),
is_active (bool), order_index (int), version, timestamps, soft_deletes

UNIQUE (org_id, name)
```

Seeded by `AssetCategorySeeder` with: Laptop, Accessories, Phone, Badge ID.

### `assets` table

```
id, org_id, asset_category_id (FK), name (string — e.g., "Dell XPS 15"), serial_number (nullable),
model (nullable string), value_piasters (unsigned bigint), acquired_date (nullable date — when YZH bought it),
condition_at_acquisition (enum 'new'|'used'|'refurbished'),
current_status (enum 'in_pool'|'assigned'|'lost'|'damaged'|'written_off'),
current_employee_id (nullable FK to employees), notes (text nullable),
version, timestamps, soft_deletes

UNIQUE (org_id, serial_number) where serial_number IS NOT NULL.
```

### `asset_assignments` table — chain of custody

```
id, org_id, asset_id (FK), employee_id (FK), assigned_at (datetime),
expected_return_at (nullable datetime — for loaners), returned_at (nullable datetime),
age_at_assignment_months (unsigned int — Walid's Option B snapshot),
condition_at_assignment (enum same as condition_at_acquisition),
return_condition (nullable enum 'good'|'damaged'|'lost'),
return_notes (text nullable),
assigned_by_user_id (FK to users), returned_by_user_id (nullable FK),
notes (text nullable),
timestamps  -- no soft delete; assignments are immutable history
```

`Auditable` on both `assets` and `asset_assignments`.

## Routes

| Method | URL | Permission | Action |
|---|---|---|---|
| `GET` | `/assets` | role-scoped (employee = own; manager = own; HR/Admin = all) | List |
| `GET` | `/assets/{asset}` | `assets.view.any` (HR/Admin only) | Detail with full chain |
| `POST` | `/assets` | `assets.create` | Create new asset (in_pool by default) |
| `PATCH` | `/assets/{asset}` | `assets.create` | Update asset metadata |
| `POST` | `/assets/{asset}/assign` | `assets.assign` | Assign to employee — creates `asset_assignment` + sets `current_employee_id` |
| `POST` | `/assets/{asset}/return` | `assets.assign` | Return — closes assignment + sets status back to in_pool |
| `POST` | `/assets/{asset}/mark-lost` | `assets.assign` | Mark lost (notes required) |
| `POST` | `/assets/{asset}/mark-damaged` | `assets.assign` | Mark damaged (notes required) |
| `DELETE` | `/assets/{asset}` | `assets.delete` (Admin only) | Soft-delete asset |
| `GET` | `/admin/asset-categories` | `settings.asset_categories.manage` | Settings page |
| `POST` | `/admin/asset-categories` | `settings.asset_categories.manage` | Add category |
| `PATCH` | `/admin/asset-categories/{cat}` | `settings.asset_categories.manage` | Edit category |
| `DELETE` | `/admin/asset-categories/{cat}` | `settings.asset_categories.manage` | Soft-delete (only if no assets) |

### New permission strings

Adding to `RoleDefinitions`:
- `assets.create` → HR + Admin
- `assets.assign` → HR + Admin
- `assets.view.own` → All roles
- `assets.view.any` → HR + Admin
- `assets.delete` → Admin only
- `settings.asset_categories.manage` → Admin only

## Frontend pages

1. **`Pages/Assets/Index.tsx`** — sidebar landing.
   - **Employee + Manager** view: list of "Your assets" — current assignments only, columns: category / name / serial / value / age (months) / assigned-since.
   - **HR + Admin** view: full org assets list, columns: name / category / serial / current employee / status / value / acquired date. Filters: category, status, employee, has-serial. Export to CSV/Excel.
2. **`Pages/Assets/Show.tsx`** (HR+Admin only) — detail page with full chain-of-custody.
3. **`Pages/Assets/Create.tsx`** (HR+Admin) — create new asset form.
4. **`Pages/Assets/Assign.tsx`** (HR+Admin) — assignment form. Pick employee from dropdown, enter `age_at_assignment_months`, expected return date if loaner, condition.
5. **`Pages/Employees/Show.tsx` — Assets tab** (HR/Admin only — employees use the sidebar item) — same data as Index filtered to this employee, plus chain history.
6. **`Pages/Settings/AssetCategories.tsx`** (Admin) — manage the canonical list.

## Edge cases

- **Reassignment chain:** Asset A was assigned to Employee X, returned, now assigned to Employee Y. Both X's and Y's history rows persist; X's row shows `returned_at` set, Y's row is currently active. Asset detail page shows the full chain in chronological order.
- **Asset deletion when currently assigned:** block. Force "return" first, then delete.
- **Category deletion when assets exist:** soft-delete the category but block reassignment to it; existing assets keep the category reference (read-only).
- **Lost / damaged assets:** still appear in HR's roster but with status badge; cannot be reassigned until status reset.
- **Serial number uniqueness:** scoped to org. Same model can have the same serial across tenants (multi-tenant correctness).
- **Multi-tenant:** `OrgScope` auto-isolates assets, categories, assignments. Test verified.

## Realistic seed (medium depth per Walid 2026-04-30)

For 28 seeded employees:

- **95%** have a Laptop assigned (random model: Dell XPS 15, MacBook Pro 14, Lenovo ThinkPad X1, HP EliteBook 840, Asus ZenBook 14)
- **60%** also have a Phone (iPhone 14, Samsung Galaxy S23, Pixel 7, Xiaomi Redmi)
- **30%** also have Accessories (mouse + keyboard + headset bundle)
- **0%** have Badge ID (Walid: "no badges yet")
- Asset values: realistic ranges
  - Laptops: 25,000 – 90,000 EGP (2,500,000 – 9,000,000 piasters)
  - Phones: 15,000 – 40,000 EGP (1,500,000 – 4,000,000 piasters)
  - Accessories: 1,000 – 5,000 EGP (100,000 – 500,000 piasters)
- `acquired_date`: random in last 36 months
- `age_at_assignment_months`: random 0-24 months (snapshot at assignment)
- A few assets in `in_pool` (returned, awaiting reassignment) to demonstrate the pool concept
- 2-3 historic returns recorded (assigned to one employee, returned, reassigned to another) so the chain-of-custody UI has real data

## Test plan

`tests/Feature/Assets/`:

- `AssetCreateTest.php` — HR/Admin can create; Employee/Manager cannot.
- `AssetAssignmentTest.php` — assigning an asset creates a row, sets current_employee_id, and ages_months matches input.
- `AssetReturnTest.php` — returning closes the assignment, resets status to in_pool, return_condition required.
- `AssetVisibilityTest.php` — Employee sees only own current; Manager sees only own current; HR/Admin sees all.
- `AssetMultiTenantTest.php` — assets in org A invisible to org B HR.
- `AssetChainOfCustodyTest.php` — reassigning preserves history.
- `AssetCategoryTest.php` — Admin can add/edit/disable categories; HR cannot add/disable.
- `AssetExportTest.php` — CSV + Excel export honors the role scope.
- `AssetSerialUniquenessTest.php` — same serial in different orgs allowed; same serial twice in same org blocked.

Estimated ~22 tests.

## Build estimate

~1 day: schema + migrations (2hr) + N-tier backend (3hr) + frontend pages (5hr) + Settings page (1.5hr) + tests (3hr) + seed overhaul (1.5hr).
