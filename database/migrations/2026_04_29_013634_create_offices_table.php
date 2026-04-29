<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('name', 100);
            $table->string('address', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('allowed_check_in_radius_meters')->default(150);
            $table->string('timezone', 50)->default('Africa/Cairo');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('org_id');
            $table->index(['org_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
