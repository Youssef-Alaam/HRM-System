<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->nullable()
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('action', 50); // created / updated / deleted / restored / login / logout / login_failed / scope_bypass
            $table->string('entity_type', 100)->nullable(); // e.g. App\Models\Employee
            $table->string('entity_id', 100)->nullable();   // string to allow UUIDs later
            $table->json('changes')->nullable();            // before/after diff
            $table->string('ip_address', 45)->nullable();   // v4 or v6
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            // No updated_at — audit rows are immutable.

            $table->index('org_id');
            $table->index('user_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index(['org_id', 'action']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
