<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('head_employee_id')
                ->nullable()
                ->after('parent_department_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->index('head_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['head_employee_id']);
            $table->dropForeign(['head_employee_id']);
            $table->dropColumn('head_employee_id');
        });
    }
};
