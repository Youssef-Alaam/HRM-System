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
        // Egyptian income tax brackets per Income Tax Law 91/2005.
        // Rates change annually with the budget — keyed by year so payroll
        // re-runs of historical periods use the brackets in force at the time.
        // Stored in piasters (1 EGP = 100 piasters) per CLAUDE.md money rule.
        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('tier');               // 1-based ordinal
            $table->unsignedBigInteger('min_piasters');        // inclusive lower bound (annual)
            $table->unsignedBigInteger('max_piasters')->nullable(); // inclusive upper, NULL = top open bracket
            $table->decimal('rate', 5, 4);                     // e.g. 0.1000 for 10%
            $table->timestamps();

            $table->unique(['year', 'tier']);
            $table->index('year');
        });

        // Personal allowance + SI caps + SI rates by year — single source of truth
        // for payroll. All editable in admin Settings, but seeded with 2026 values.
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedBigInteger('personal_allowance_piasters');     // tax-free annual
            $table->unsignedBigInteger('si_insurable_floor_piasters');     // monthly
            $table->unsignedBigInteger('si_insurable_cap_piasters');       // monthly
            $table->decimal('si_employee_rate', 5, 4);                     // 0.1100
            $table->decimal('si_employer_rate', 5, 4);                     // 0.1875
            $table->decimal('health_employee_rate', 5, 4);                 // 0.0100
            $table->decimal('health_employer_rate', 5, 4);                 // 0.0325
            $table->decimal('training_employer_rate', 5, 4);               // 0.0025
            $table->unsignedInteger('minimum_wage_piasters');              // monthly
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
        Schema::dropIfExists('tax_brackets');
    }
};
