<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds face-enrollment columns to employees:
 * - face_descriptor — canonical 128-element vector (mean of 3 enrollment
 *   photos), used by Feature 5 attendance to verify check-in selfies.
 * - last_face_enrollment_at — drives the 12-month re-enrollment cadence
 *   and the dashboard "due this month" widget.
 * - failed_checkin_count — incremented on every face-mismatch at check-in
 *   and reset on success or HR reset. Reaches 5 → forced re-enrollment.
 * - requires_face_reenrollment — sticky flag set by either trigger; the
 *   next attendance check-in is blocked until HR re-enrolls.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->json('face_descriptor')->nullable()->after('reference_photo_url');
            $table->timestamp('last_face_enrollment_at')->nullable()->after('face_descriptor');
            $table->unsignedInteger('failed_checkin_count')->default(0)->after('last_face_enrollment_at');
            $table->boolean('requires_face_reenrollment')->default(false)->after('failed_checkin_count');
            $table->index(['org_id', 'last_face_enrollment_at']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['org_id', 'last_face_enrollment_at']);
            $table->dropColumn([
                'face_descriptor',
                'last_face_enrollment_at',
                'failed_checkin_count',
                'requires_face_reenrollment',
            ]);
        });
    }
};
