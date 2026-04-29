<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('name', 200);
            $table->date('date');
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_make_up')->default(false); // govt-declared make-up day per Decision 9
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['org_id', 'date']);
            $table->index('org_id');
            $table->index(['org_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
