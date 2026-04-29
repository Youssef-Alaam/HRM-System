<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('org_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->unique('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
