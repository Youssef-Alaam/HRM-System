<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Employee;
use App\Repositories\Contracts\AssetRepositoryInterface;
use Carbon\CarbonImmutable;
use DomainException;

/**
 * Layer-3 service for the Assets feature.
 *
 * Locked 2026-04-30 with Walid:
 * - 1:1 assignment (no reservation pool).
 * - Reassignment closes the previous assignment row, opens a new one.
 * - Mark-lost / mark-damaged transitions force an open assignment to
 *   close out (the asset is gone from the employee's hands).
 * - Cannot delete an asset that's still assigned — controller flow must
 *   force a return first.
 *
 * Money values stay in piasters per Decision 8.
 */
class AssetService extends BaseService
{
    public function __construct(
        private readonly AssetRepositoryInterface $repo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Asset
    {
        return $this->transaction(function () use ($data) {
            return $this->repo->create(array_merge([
                'org_id' => auth()->user()->org_id,
                'current_status' => Asset::STATUS_IN_POOL,
            ], $data));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Asset
    {
        return $this->transaction(function () use ($id, $data) {
            $asset = $this->repo->findOrFail($id);

            return $this->repo->update($asset, $data);
        });
    }

    public function assign(
        int $assetId,
        Employee $employee,
        string $conditionAtAssignment,
        ?string $expectedReturnAt,
        ?string $notes,
        int $assignedByUserId,
    ): AssetAssignment {
        return $this->transaction(function () use (
            $assetId, $employee, $conditionAtAssignment,
            $expectedReturnAt, $notes, $assignedByUserId,
        ) {
            $asset = $this->repo->findOrFail($assetId);

            if (! in_array($asset->current_status, [
                Asset::STATUS_IN_POOL,
                Asset::STATUS_ASSIGNED,
            ], true)) {
                throw new DomainException(
                    "Asset is currently {$asset->current_status} and cannot be assigned. Reset its status first.",
                );
            }

            // Reassignment: close the existing open assignment if any.
            $open = AssetAssignment::query()
                ->where('asset_id', $assetId)
                ->whereNull('returned_at')
                ->first();
            if ($open !== null) {
                $open->returned_at = CarbonImmutable::now();
                $open->returned_by_user_id = $assignedByUserId;
                $open->return_condition = 'good'; // assumption; HR may patch
                $open->save();
            }

            // Snapshot the asset's age at assignment time. Computed from
            // acquired_date so HR doesn't have to enter it manually
            // (Walid revised the original Option B spec on 2026-04-30 —
            // the snapshot semantic stays, only the source moves from
            // "HR types" to "auto-derived from acquired_date").
            $ageMonths = $asset->acquired_date
                ? (int) max(0, $asset->acquired_date->diffInMonths(CarbonImmutable::now()))
                : 0;

            $assignment = AssetAssignment::create([
                'org_id' => $asset->org_id,
                'asset_id' => $asset->id,
                'employee_id' => $employee->id,
                'assigned_at' => CarbonImmutable::now(),
                'expected_return_at' => $expectedReturnAt,
                'age_at_assignment_months' => $ageMonths,
                'condition_at_assignment' => $conditionAtAssignment,
                'assigned_by_user_id' => $assignedByUserId,
                'notes' => $notes,
            ]);

            $this->repo->update($asset, [
                'current_status' => Asset::STATUS_ASSIGNED,
                'current_employee_id' => $employee->id,
            ]);

            return $assignment;
        });
    }

    public function returnAsset(
        int $assetId,
        string $returnCondition,
        ?string $returnNotes,
        int $returnedByUserId,
    ): void {
        $this->transaction(function () use ($assetId, $returnCondition, $returnNotes, $returnedByUserId) {
            $asset = $this->repo->findOrFail($assetId);

            if ($asset->current_status !== Asset::STATUS_ASSIGNED) {
                throw new DomainException('Asset is not currently assigned.');
            }

            $open = AssetAssignment::query()
                ->where('asset_id', $assetId)
                ->whereNull('returned_at')
                ->firstOrFail();

            $open->returned_at = CarbonImmutable::now();
            $open->returned_by_user_id = $returnedByUserId;
            $open->return_condition = $returnCondition;
            $open->return_notes = $returnNotes;
            $open->save();

            // good = back into pool; damaged/lost = mirror the return into
            // asset.current_status so HR can spot it on the roster.
            $newStatus = match ($returnCondition) {
                'damaged' => Asset::STATUS_DAMAGED,
                'lost' => Asset::STATUS_LOST,
                default => Asset::STATUS_IN_POOL,
            };

            $this->repo->update($asset, [
                'current_status' => $newStatus,
                'current_employee_id' => null,
            ]);
        });
    }

    public function markLost(int $assetId, string $notes, int $actorUserId): void
    {
        $this->markIncident($assetId, Asset::STATUS_LOST, 'lost', $notes, $actorUserId);
    }

    public function markDamaged(int $assetId, string $notes, int $actorUserId): void
    {
        $this->markIncident($assetId, Asset::STATUS_DAMAGED, 'damaged', $notes, $actorUserId);
    }

    public function softDelete(int $assetId): void
    {
        $this->transaction(function () use ($assetId) {
            $asset = $this->repo->findOrFail($assetId);

            if ($asset->current_status === Asset::STATUS_ASSIGNED) {
                throw new DomainException(
                    'Cannot delete an assigned asset. Force a return first.',
                );
            }

            $this->repo->softDelete($asset);
        });
    }

    private function markIncident(
        int $assetId,
        string $newStatus,
        string $returnCondition,
        string $notes,
        int $actorUserId,
    ): void {
        $this->transaction(function () use ($assetId, $newStatus, $returnCondition, $notes, $actorUserId) {
            $asset = $this->repo->findOrFail($assetId);

            // Close any open assignment with the incident condition so the
            // chain shows who held the asset when it went missing/broke.
            $open = AssetAssignment::query()
                ->where('asset_id', $assetId)
                ->whereNull('returned_at')
                ->first();
            if ($open !== null) {
                $open->returned_at = CarbonImmutable::now();
                $open->returned_by_user_id = $actorUserId;
                $open->return_condition = $returnCondition;
                $open->return_notes = $notes;
                $open->save();
            }

            $this->repo->update($asset, [
                'current_status' => $newStatus,
                'current_employee_id' => null,
                'notes' => trim(($asset->notes ? $asset->notes."\n\n" : '').$notes),
            ]);
        });
    }
}
