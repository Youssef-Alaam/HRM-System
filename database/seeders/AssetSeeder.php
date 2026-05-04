<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Realistic medium-depth asset inventory (locked Walid 2026-04-30):
 * - 95% of employees have a Laptop
 * - 60% also have a Phone
 * - 30% also have Accessories
 * - 0% Badge ID (Walid: "no badges yet")
 * - A few in_pool laptops awaiting reassignment
 * - 2-3 historic returns (X → Y reassignment) for chain-of-custody demo
 */
class AssetSeeder extends Seeder
{
    private const LAPTOP_MODELS = [
        'Dell XPS 15', 'MacBook Pro 14', 'Lenovo ThinkPad X1 Carbon',
        'HP EliteBook 840', 'Asus ZenBook 14',
    ];

    private const PHONE_MODELS = [
        'iPhone 14', 'Samsung Galaxy S23', 'Google Pixel 7', 'Xiaomi Redmi Note 12',
    ];

    private const ACCESSORY_BUNDLES = [
        'Logitech MX Master 3 + MX Keys',
        'Apple Magic Mouse + Magic Keyboard',
        'Razer BlackWidow + Razer DeathAdder',
        'Sony WH-1000XM5 headset bundle',
    ];

    public function run(): void
    {
        $org = Organization::query()->where('name', 'YZH Solutions')->firstOrFail();
        $hr = User::query()
            ->where('org_id', $org->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'hr'))
            ->first()
            ?: User::query()->where('org_id', $org->id)->firstOrFail();

        $laptopCat = AssetCategory::query()->where('org_id', $org->id)->where('name', 'Laptop')->firstOrFail();
        $phoneCat = AssetCategory::query()->where('org_id', $org->id)->where('name', 'Phone')->firstOrFail();
        $accCat = AssetCategory::query()->where('org_id', $org->id)->where('name', 'Accessories')->firstOrFail();

        $employees = Employee::query()->where('org_id', $org->id)->get();
        if ($employees->isEmpty()) {
            return;
        }

        foreach ($employees as $i => $employee) {
            // 95% chance of laptop
            if ($i === 0 || rand(1, 100) <= 95) {
                $model = static::LAPTOP_MODELS[array_rand(static::LAPTOP_MODELS)];
                $this->createAndAssign(
                    $org->id, $hr->id, $employee->id, $laptopCat->id,
                    name: $model,
                    serial: 'LAP-'.strtoupper(Str::random(8)),
                    valuePiasters: rand(2_500_000, 9_000_000),
                );
            }

            // 60% chance of phone
            if (rand(1, 100) <= 60) {
                $model = static::PHONE_MODELS[array_rand(static::PHONE_MODELS)];
                $this->createAndAssign(
                    $org->id, $hr->id, $employee->id, $phoneCat->id,
                    name: $model,
                    serial: 'PHN-'.strtoupper(Str::random(8)),
                    valuePiasters: rand(1_500_000, 4_000_000),
                );
            }

            // 30% chance of accessories
            if (rand(1, 100) <= 30) {
                $bundle = static::ACCESSORY_BUNDLES[array_rand(static::ACCESSORY_BUNDLES)];
                $this->createAndAssign(
                    $org->id, $hr->id, $employee->id, $accCat->id,
                    name: $bundle,
                    serial: null,
                    valuePiasters: rand(100_000, 500_000),
                );
            }
        }

        // A couple of in_pool laptops awaiting reassignment.
        for ($i = 0; $i < 2; $i++) {
            Asset::create([
                'org_id' => $org->id,
                'asset_category_id' => $laptopCat->id,
                'name' => static::LAPTOP_MODELS[array_rand(static::LAPTOP_MODELS)],
                'serial_number' => 'LAP-POOL-'.strtoupper(Str::random(6)),
                'value_piasters' => rand(2_500_000, 9_000_000),
                'acquired_date' => CarbonImmutable::now()->subMonths(rand(1, 36))->toDateString(),
                'condition_at_acquisition' => 'new',
                'current_status' => Asset::STATUS_IN_POOL,
            ]);
        }

        // 2 historic chains: an asset assigned, returned, then reassigned.
        // Demonstrates the chain-of-custody UI.
        $this->seedHistoricChain($org->id, $hr->id, $employees, $laptopCat->id);
        $this->seedHistoricChain($org->id, $hr->id, $employees, $phoneCat->id);
    }

    private function createAndAssign(
        int $orgId,
        int $hrUserId,
        int $employeeId,
        int $categoryId,
        string $name,
        ?string $serial,
        int $valuePiasters,
    ): Asset {
        $acquired = CarbonImmutable::now()->subMonths(rand(1, 36));
        $assignedAt = $acquired->addDays(rand(1, 30));

        $asset = Asset::create([
            'org_id' => $orgId,
            'asset_category_id' => $categoryId,
            'name' => $name,
            'serial_number' => $serial,
            'value_piasters' => $valuePiasters,
            'acquired_date' => $acquired->toDateString(),
            'condition_at_acquisition' => 'new',
            'current_status' => Asset::STATUS_ASSIGNED,
            'current_employee_id' => $employeeId,
        ]);

        AssetAssignment::create([
            'org_id' => $orgId,
            'asset_id' => $asset->id,
            'employee_id' => $employeeId,
            'assigned_at' => $assignedAt,
            'age_at_assignment_months' => rand(0, 24),
            'condition_at_assignment' => 'new',
            'assigned_by_user_id' => $hrUserId,
        ]);

        return $asset;
    }

    private function seedHistoricChain(int $orgId, int $hrUserId, $employees, int $categoryId): void
    {
        if ($employees->count() < 2) {
            return;
        }

        $first = $employees->random();
        $second = $employees->where('id', '!=', $first->id)->random();

        $acquiredAt = CarbonImmutable::now()->subMonths(24);
        $firstAssignedAt = $acquiredAt->addDays(7);
        $returnedAt = $firstAssignedAt->addMonths(8);
        $secondAssignedAt = $returnedAt->addDays(2);

        $asset = Asset::create([
            'org_id' => $orgId,
            'asset_category_id' => $categoryId,
            'name' => 'Reassigned ' . static::LAPTOP_MODELS[array_rand(static::LAPTOP_MODELS)],
            'serial_number' => 'CHN-'.strtoupper(Str::random(8)),
            'value_piasters' => rand(2_500_000, 9_000_000),
            'acquired_date' => $acquiredAt->toDateString(),
            'condition_at_acquisition' => 'new',
            'current_status' => Asset::STATUS_ASSIGNED,
            'current_employee_id' => $second->id,
        ]);

        AssetAssignment::create([
            'org_id' => $orgId,
            'asset_id' => $asset->id,
            'employee_id' => $first->id,
            'assigned_at' => $firstAssignedAt,
            'returned_at' => $returnedAt,
            'returned_by_user_id' => $hrUserId,
            'age_at_assignment_months' => 0,
            'condition_at_assignment' => 'new',
            'return_condition' => 'good',
            'return_notes' => 'Returned on role change.',
            'assigned_by_user_id' => $hrUserId,
        ]);

        AssetAssignment::create([
            'org_id' => $orgId,
            'asset_id' => $asset->id,
            'employee_id' => $second->id,
            'assigned_at' => $secondAssignedAt,
            'age_at_assignment_months' => 8,
            'condition_at_assignment' => 'used',
            'assigned_by_user_id' => $hrUserId,
            'notes' => 'Reassignment from previous owner.',
        ]);
    }
}
