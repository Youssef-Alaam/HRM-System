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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('office_id')
                ->nullable()
                ->constrained('offices')
                ->nullOnDelete();

            $table->enum('type', ['check_in', 'check_out']);
            $table->timestamp('event_at');           // UTC timestamp of event
            $table->date('event_date');              // Cairo-bucketed date for grouping
            $table->boolean('is_late')->default(false);

            // Geo
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('distance_meters', 10, 2)->nullable();   // distance to nearest office

            // Face verification (Decision 7)
            $table->enum('verdict', ['verified', 'possibly_self', 'unverified', 'bypassed'])->default('bypassed');
            $table->decimal('verdict_score', 5, 4)->nullable();      // 0.0000 → 1.0000
            $table->string('selfie_path', 500)->nullable();          // PDPL: auto-purge 24h
            $table->timestamp('selfie_deleted_at')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('corrected_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('corrected_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['org_id', 'employee_id', 'event_date']);
            $table->index(['org_id', 'event_date']);
            $table->index('verdict');
            $table->index('selfie_deleted_at');   // selfie purge cron query
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
