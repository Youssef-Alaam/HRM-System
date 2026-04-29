<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('parent_department_id')
                ->nullable()
                ->constrained('departments')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            // head_employee_id added in a later migration after employees table exists
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('org_id');
            $table->index(['org_id', 'parent_department_id']);
            $table->index(['org_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
