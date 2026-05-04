<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assets — physical equipment owned by the org. 1:1 assignment to a
 * single employee at a time (no reservation pool per Walid 2026-04-30).
 * Value tracked in piasters per Decision 8. No depreciation calc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('asset_category_id')
                ->constrained('asset_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('name', 200); // "Dell XPS 15"
            $table->string('serial_number', 100)->nullable();
            $table->string('model', 200)->nullable();
            $table->unsignedBigInteger('value_piasters')->default(0);
            $table->date('acquired_date')->nullable();
            $table->enum('condition_at_acquisition', ['new', 'used', 'refurbished'])
                ->default('new');
            $table->enum('current_status', [
                'in_pool', 'assigned', 'lost', 'damaged', 'written_off',
            ])->default('in_pool');
            $table->foreignId('current_employee_id')
                ->nullable()
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            // Serial uniqueness scoped to org (multi-tenant correctness).
            // Same serial in different orgs is allowed; the index uses
            // both columns and SQLite/MySQL treat NULL as distinct so
            // multiple null-serial assets are permitted.
            $table->unique(['org_id', 'serial_number']);
            $table->index(['org_id', 'current_status']);
            $table->index(['org_id', 'asset_category_id']);
            $table->index(['org_id', 'current_employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
