<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asset categories — extensible per-org list. Locked 2026-04-30 with
 * Walid: starter set is Laptop / Accessories / Phone / Badge ID; HR
 * adds new categories from Settings (e.g. helmets later).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            // lucide-react icon name; defaults to a generic package.
            $table->string('icon_name', 50)->default('package');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('order_index')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['org_id', 'name']);
            $table->index(['org_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
