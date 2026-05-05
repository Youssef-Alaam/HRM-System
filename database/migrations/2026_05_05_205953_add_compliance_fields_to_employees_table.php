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
        Schema::table('employees', function (Blueprint $table) {
            // Termination fields (per Labor Law Art. 110-115)
            $table->date('termination_date')->nullable()->after('employment_status');
            $table->string('termination_reason', 100)->nullable()->after('termination_date');
            $table->date('termination_notice_date')->nullable()->after('termination_reason');
            $table->unsignedBigInteger('eosb_piasters')->nullable()->after('termination_notice_date');

            // Retirement extension (per Social Insurance Law 148/2019 §11)
            $table->date('retirement_extended_until')->nullable()->after('eosb_piasters');

            // Deemed resignation tracking (per Labor Law Art. 69)
            $table->unsignedInteger('unauthorized_absences_ytd')->default(0)->after('retirement_extended_until');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'termination_date',
                'termination_reason',
                'termination_notice_date',
                'eosb_piasters',
                'retirement_extended_until',
                'unauthorized_absences_ytd',
            ]);
        });
    }
};
