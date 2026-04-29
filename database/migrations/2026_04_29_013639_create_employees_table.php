<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            // Identity
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('employee_code', 50);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255);
            $table->string('phone', 30)->nullable();
            $table->string('national_id', 14)->nullable();

            // Demographics
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('nationality', 100)->default('Egyptian');
            $table->string('address', 500)->nullable();

            // Emergency contact
            $table->string('emergency_contact_name', 200)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();

            // Photos / face verification (per Decision 7)
            $table->string('photo_url', 500)->nullable();
            $table->string('reference_photo_url', 500)->nullable();

            // Org assignment
            $table->foreignId('position_id')
                ->nullable()
                ->constrained('positions')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('office_id')
                ->nullable()
                ->constrained('offices')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->date('hiring_date')->nullable();

            // Contract
            $table->enum('contract_type', [
                'probation', 'fixed', 'unlimited', 'part_time', 'internship', 'project',
            ])->default('probation');
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->enum('employment_status', [
                'active', 'suspended', 'on_leave', 'terminated', 'deemed_resigned', 'retired', 'probation',
            ])->default('active');
            $table->json('workweek_days')->nullable();
            $table->time('shift_start_time')->nullable();
            $table->time('shift_end_time')->nullable();
            $table->string('timezone', 50)->default('Africa/Cairo');

            // Compensation (piasters per Decision 8)
            $table->unsignedBigInteger('base_salary_piasters')->default(0);

            // Leave balances (per Decision 18 — universal law-minimum from Day 1)
            $table->decimal('annual_leave_balance_days', 5, 2)->default(0);
            $table->decimal('sick_leave_balance_days', 5, 2)->default(0);
            $table->decimal('casual_leave_balance_days', 5, 2)->default(0);
            $table->unsignedInteger('permissions_balance_minutes')->default(0);
            $table->decimal('emergency_credit_days', 5, 2)->default(0);
            $table->decimal('comp_day_balance', 5, 2)->default(0);

            // Health / disability flags (drive payroll calc per compliance rules)
            $table->boolean('chronic_illness')->default(false);
            $table->date('chronic_illness_certified_at')->nullable();
            $table->boolean('has_disability')->default(false);

            // Expat fields (per Decision 6)
            $table->boolean('is_expat')->default(false);
            $table->string('passport_number', 50)->nullable();
            $table->date('passport_expiry')->nullable();
            $table->string('work_permit_number', 50)->nullable();
            $table->date('work_permit_expiry')->nullable();
            $table->string('residency_permit_number', 50)->nullable();
            $table->date('residency_permit_expiry')->nullable();
            $table->boolean('speaks_arabic')->default(true);

            // Director SI rate flag (per compliance §13)
            $table->boolean('is_on_commercial_register')->default(false);

            // Concurrency + audit
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            // Uniqueness scoped to org
            $table->unique(['org_id', 'employee_code']);
            $table->unique(['org_id', 'email']);
            $table->unique(['org_id', 'national_id']);

            // Hot path indexes
            $table->index('org_id');
            $table->index(['org_id', 'employment_status']);
            $table->index(['org_id', 'department_id']);
            $table->index(['org_id', 'office_id']);
            $table->index(['org_id', 'manager_id']);
            $table->index(['org_id', 'hiring_date']);
            $table->index('is_expat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
