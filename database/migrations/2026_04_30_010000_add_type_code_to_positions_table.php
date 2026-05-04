<?php

use App\Support\PositionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds positions.type_code (0-9) — first digit of the EMP-XXXXX employee
 * code per the 2026-04-30 design lock with Walid. Existing seeded rows
 * are reclassified by title-keyword inference; new positions ship with
 * an explicit type_code from the seeder/factory or fall back to OTHER (9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->unsignedTinyInteger('type_code')
                ->default(PositionType::OTHER)
                ->after('level');
            $table->index(['org_id', 'type_code']);
        });

        // Reclassify existing rows by title keyword. Direct query intentionally
        // — bypasses the BelongsToOrg global scope (no auth user) and avoids
        // model events firing while we're mid-migration.
        DB::table('positions')->orderBy('id')->each(function ($row) {
            $code = PositionType::inferFromTitle($row->title);
            DB::table('positions')->where('id', $row->id)->update(['type_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropIndex(['org_id', 'type_code']);
            $table->dropColumn('type_code');
        });
    }
};
