<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds employees.dependents — number of legal dependents the employee
 * supports (used by Egyptian payroll for tax + benefit calculations).
 * Locked 2026-04-30 with Walid as a Self-tier field on the Profile page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedTinyInteger('dependents')->default(0)->after('marital_status');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('dependents');
        });
    }
};
