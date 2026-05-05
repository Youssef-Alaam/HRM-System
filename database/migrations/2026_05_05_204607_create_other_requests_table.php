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
        Schema::create('other_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('type', ['overtime', 'expense_claim', 'change_shift', 'holiday_work']);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->date('request_date');
            // Per Labor Law Art. 85 — overtime cap 2 hrs/day
            $table->decimal('hours_requested', 4, 2)->nullable();
            // Stored in piasters per CLAUDE.md money convention
            $table->unsignedInteger('amount_piasters')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['org_id', 'employee_id', 'status']);
            $table->index(['org_id', 'status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_requests');
    }
};
