<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('title', 100);
            $table->string('description', 500)->nullable();
            $table->unsignedTinyInteger('level')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('org_id');
            $table->index(['org_id', 'department_id']);
            $table->index(['org_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
