<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asset assignments — immutable chain of custody. Each row records one
 * (asset, employee) custody window. Reassignment closes the previous
 * row (returned_at) and opens a new one. No soft delete: history must
 * be preserved.
 *
 * `age_at_assignment_months` is Walid's Option B snapshot (locked
 * 2026-04-30). HR enters it manually at assignment, not auto-derived
 * from acquired_date — Walid's call.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expected_return_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->unsignedSmallInteger('age_at_assignment_months')->default(0);
            $table->enum('condition_at_assignment', ['new', 'used', 'refurbished'])
                ->default('new');
            $table->enum('return_condition', ['good', 'damaged', 'lost'])->nullable();
            $table->text('return_notes')->nullable();
            $table->foreignId('assigned_by_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('returned_by_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['org_id', 'asset_id']);
            $table->index(['org_id', 'employee_id']);
            $table->index(['org_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
