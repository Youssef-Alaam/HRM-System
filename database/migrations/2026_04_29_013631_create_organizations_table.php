<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('legal_name', 200)->nullable();
            $table->char('country', 2)->default('EG');
            $table->char('currency', 3)->default('EGP');
            $table->string('timezone', 50)->default('Africa/Cairo');
            $table->string('address', 500)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('registration_number', 50)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
