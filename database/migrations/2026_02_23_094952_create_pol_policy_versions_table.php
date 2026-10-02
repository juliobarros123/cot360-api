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
        Schema::create('pol_policy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('pol_policies')->cascadeOnDelete();
            $table->integer('version');
            $table->date('effective_date');
            $table->date('expiry_date');
            $table->unsignedBigInteger('commercial_agent_role_id')->nullable();
            $table->foreignId('sales_channel_id')->nullable()->constrained('ent_sales_channels')->nullOnDelete();
            $table->foreignId('cae_rate_id')->nullable()->constrained('cfg_rates_cae')->nullOnDelete();
            $table->decimal('net_premium', 18, 2);
            $table->decimal('total_taxes', 18, 2);
            $table->decimal('gross_premium', 18, 2);
            $table->string('currency', 3)->default('AOA');
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('gross_premium_usd', 18, 2)->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->decimal('commission_value', 18, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->enum('status', ['Active', 'Superseded', 'Cancelled'])->default('Active');
            $table->json('snapshot_json');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->foreign('commercial_agent_role_id')->references('id')->on('ent_entity_roles')->nullOnDelete();
            $table->index('policy_id');
            $table->unique(['policy_id', 'version']);
            $table->index('status');
            $table->index(['effective_date', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pol_policy_versions');
    }
};
