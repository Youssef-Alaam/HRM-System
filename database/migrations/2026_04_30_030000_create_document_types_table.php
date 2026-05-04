<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Document types — the canonical required-document matrix per org.
 * Locked 2026-04-30 with Walid as Feature 11 Documents (Phase 1 promotion).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('name', 200);
            $table->string('name_ar', 200)->nullable();
            $table->string('description', 500)->nullable();
            // applies_to controls when this type appears in an employee's
            // required-doc matrix. Egyptian-male-only is reserved for the
            // military-service status doc (موقف من التجنيد) — not seeded yet.
            $table->enum('applies_to', ['all', 'egyptian_only', 'expat_only', 'egyptian_male_only'])
                ->default('all');
            $table->boolean('is_required')->default(false);
            // Auto-fills the upload form's expiry if set; null = no expiry.
            $table->unsignedSmallInteger('default_expiry_months')->nullable();
            $table->unsignedSmallInteger('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('org_id');
            $table->index(['org_id', 'is_active']);
            $table->index(['org_id', 'applies_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
