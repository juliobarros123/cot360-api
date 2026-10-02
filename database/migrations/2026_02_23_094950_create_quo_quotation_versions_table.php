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
        Schema::create('quo_quotation_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quo_quotations')->cascadeOnDelete();
            $table->string('version', 10);
            $table->string('table_code', 50)->nullable();
            $table->unsignedBigInteger('commercial_agent_role_id')->nullable()->comment('Papel do agente comercial');
            $table->foreignId('sales_channel_id')->nullable()->constrained('ent_sales_channels')->nullOnDelete();
            $table->foreignId('cae_rate_id')->nullable()->constrained('cfg_rates_cae')->nullOnDelete();
            $table->decimal('total_premium', 20, 2)->nullable();
            $table->double('discount_percent')->nullable();
            $table->string('currency', 3)->default('AOA');
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('total_premium_usd', 20, 2)->nullable();
            $table->enum('status', ['Pending', 'Accepted', 'Rejected', 'Draft', 'Alt_Version'])->default('Pending');
            $table->date('created_date');
            $table->date('valid_until');
            $table->unsignedBigInteger('policy_id')->nullable()->comment('Preenchido quando convertida em apólice');
            $table->json('snapshot_json')->nullable();
            $table->string('template_code')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('commercial_agent_role_id')->references('id')->on('ent_entity_roles')->nullOnDelete();
            $table->index('quotation_id');
            $table->unique(['quotation_id', 'version', 'table_code'], 'quo_quotation_versions_quotation_id_version_table_code_unique');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quo_quotation_versions');
    }
};
