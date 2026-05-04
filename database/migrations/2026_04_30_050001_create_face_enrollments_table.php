<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Face enrollment history. The active descriptor lives on
 * employees.face_descriptor for hot-path reads at check-in time; this
 * table preserves every prior enrollment so HR can see the timeline
 * (e.g. annual re-enrollments after a beard change).
 *
 * Locked 2026-04-30 with Walid: 3 photos, 12-month cadence, 5-failed
 * trigger. PDPL purge wipes descriptor + photos 24h after termination.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->foreignId('enrolled_by_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unsignedTinyInteger('photo_count')->default(3);
            $table->decimal('descriptor_quality_score', 3, 2)->default(0);
            // Mirrored on employees.face_descriptor for fast reads.
            // Stored here too so historical descriptors survive
            // re-enrollments (audit + appearance-change rollback).
            $table->json('descriptor');
            // Array of relative paths under storage/app/employee-faces/...
            $table->json('photo_paths');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['org_id', 'employee_id']);
            $table->index(['org_id', 'enrolled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_enrollments');
    }
};
