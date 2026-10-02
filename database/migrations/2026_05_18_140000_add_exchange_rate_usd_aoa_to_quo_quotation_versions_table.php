<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quo_quotation_versions', function (Blueprint $table) {
            $table->decimal('exchange_rate_usd_aoa', 18, 6)->nullable()->after('exchange_rate');
            $table->index('exchange_rate_usd_aoa', 'quo_qv_exchange_rate_usd_aoa_idx');
        });
    }

    public function down(): void
    {
        Schema::table('quo_quotation_versions', function (Blueprint $table) {
            $table->dropIndex('quo_qv_exchange_rate_usd_aoa_idx');
            $table->dropColumn('exchange_rate_usd_aoa');
        });
    }
};

