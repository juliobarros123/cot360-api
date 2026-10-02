<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfg_rates_health', function (Blueprint $table) {
            $table->string('currency', 3)->default('AOA')->after('premium_value');
            $table->index(['insurance_branch_id', 'currency'], 'cfg_rates_health_branch_currency_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cfg_rates_health', function (Blueprint $table) {
            $table->dropIndex('cfg_rates_health_branch_currency_idx');
            $table->dropColumn('currency');
        });
    }
};

