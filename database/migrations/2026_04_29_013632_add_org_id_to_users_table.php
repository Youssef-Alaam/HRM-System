<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('org_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->index('org_id');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['org_id']);
            $table->dropIndex(['org_id']);
            $table->dropColumn('org_id');
            $table->dropSoftDeletes();
        });
    }
};
