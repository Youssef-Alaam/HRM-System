<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Builds the reporting tree consumed by Pages/OrgChart.tsx.
 *
 * Rules (per specs/feature-3-org-chart.md):
 * - Only active employees appear as nodes.
 * - When an active employee's direct manager is inactive (or
 *   terminated, etc.), we skip-up the manager chain until we hit an
 *   active ancestor. Reports re-parent to that survivor instead of
 *   being orphaned. Keeps the chart current rather than haunted by
 *   terminations.
 * - Cycles in `manager_id` are guarded by a visited-set in `attach`.
 *   Bad data becomes log noise + duplicate roots, not infinite recursion.
 */
class OrgChartService extends BaseService
{
    /**
     * @return array<int, array<string, mixed>> forest of OrgNodes
     */
    public function buildTree(int $orgId): array
    {
        $employees = Employee::query()
            ->where('org_id', $orgId)
            ->with(['position:id,title', 'department:id,name'])
            ->get();

        if ($employees->isEmpty()) {
            return [];
        }

        $byId = $employees->keyBy('id');
        $active = $employees->filter(fn (Employee $e) => $e->employment_status === 'active');

        // Determine the "effective manager" for each active employee:
        // walk up the manager chain, skipping inactive ancestors.
        $effectiveParent = [];
        foreach ($active as $emp) {
            $effectiveParent[$emp->id] = $this->findActiveAncestor($emp, $byId);
        }

        // Roots = active employees whose effective parent is null.
        $roots = $active->filter(fn (Employee $e) => $effectiveParent[$e->id] === null);

        // Build the parentId → [childId, …] map. Plain foreach beats
        // collect()->groupBy() here because groupBy reindexes the
        // grouped sub-collections, dropping the original empId keys.
        $childrenOf = [];
        foreach ($effectiveParent as $empId => $parentId) {
            if ($parentId === null) {
                continue;
            }
            $childrenOf[(int) $parentId][] = (int) $empId;
        }

        return $roots
            ->map(fn (Employee $root) => $this->serializeNode($root, $byId, $childrenOf, []))
            ->values()
            ->all();
    }

    /**
     * Walk up the manager chain until we find an active employee or null.
     * Returns the active ancestor's id, or null if the employee is itself
     * a root (no manager) or every ancestor is inactive.
     *
     * Cycle-safe: a visited set short-circuits manager_id loops.
     */
    private function findActiveAncestor(Employee $emp, Collection $byId): ?int
    {
        $visited = [$emp->id => true];
        $cursor = $emp->manager_id;

        while ($cursor !== null) {
            if (isset($visited[$cursor])) {
                Log::warning('OrgChartService: manager_id cycle detected', [
                    'employee_id' => $emp->id,
                    'cycle_at' => $cursor,
                ]);

                return null;
            }
            $visited[$cursor] = true;

            $manager = $byId->get($cursor);
            if (! $manager) {
                return null;
            }
            if ($manager->employment_status === 'active') {
                return (int) $manager->id;
            }
            $cursor = $manager->manager_id;
        }

        return null;
    }

    /**
     * @param  array<int, bool>  $visited
     * @param  array<int, array<int>>  $childrenOf
     * @return array<string, mixed>
     */
    private function serializeNode(
        Employee $emp,
        Collection $byId,
        array $childrenOf,
        array $visited,
    ): array {
        $visited[$emp->id] = true;

        $childIds = $childrenOf[$emp->id] ?? [];
        $children = [];
        foreach ($childIds as $cid) {
            if (isset($visited[$cid])) {
                continue; // belt-and-suspenders against cycles missed upstream
            }
            $child = $byId->get($cid);
            if ($child) {
                $children[] = $this->serializeNode($child, $byId, $childrenOf, $visited);
            }
        }

        return [
            'id' => (int) $emp->id,
            'employee_code' => $emp->employee_code,
            'first_name' => $emp->first_name,
            'last_name' => $emp->last_name,
            'position' => $emp->position?->title,
            'department' => $emp->department?->name,
            'children' => $children,
        ];
    }
}
