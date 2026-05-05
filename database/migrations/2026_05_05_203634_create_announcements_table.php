<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('author_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('title', 255);
            $table->text('body');
            $table->enum('target_type', ['all', 'department'])->default('all');
            $table->foreignId('target_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->boolean('pinned')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['org_id', 'published_at']);
            $table->index(['org_id', 'pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
