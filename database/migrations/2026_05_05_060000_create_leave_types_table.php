<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 200);
            $table->integer('default_balance_days')->nullable();
            $table->integer('requires_certificate_after_days')->nullable();
            $table->integer('advance_notice_days')->nullable();
            $table->boolean('is_right_not_discretion')->default(false);
            $table->enum('applies_to', ['all', 'male', 'female'])->default('all');
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['org_id', 'code']);
            $table->index('org_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
