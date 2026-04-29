<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable()->after('remember_token');
            $table->string('lock_reason', 200)->nullable()->after('locked_at');
            $table->timestamp('last_login_at')->nullable()->after('lock_reason');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            $table->index('locked_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['locked_at']);
            $table->dropColumn(['locked_at', 'lock_reason', 'last_login_at', 'last_login_ip']);
        });
    }
};
