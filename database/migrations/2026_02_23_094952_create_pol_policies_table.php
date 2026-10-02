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
        Schema::create('pol_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number', 50)->unique();
            $table->foreignId('quotation_id')->constrained('quo_quotations')->restrictOnDelete();
            $table->foreignId('quotation_version_id')->constrained('quo_quotation_versions')->restrictOnDelete();
            $table->foreignId('insurance_branch_id')->constrained('cfg_insurance_branches')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('ent_entities')->restrictOnDelete();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('previous_policy_id')->nullable()->comment('Apólice anterior (renovação)');
            $table->integer('renewal_count')->default(0);
            $table->unsignedBigInteger('original_policy_id')->nullable()->comment('Primeira apólice da cadeia');
            $table->date('inception_date');
            $table->date('current_expiry_date');
            $table->enum('status', ['Active', 'Suspended', 'Cancelled', 'Expired', 'Terminated'])->default('Active');
            $table->string('slug', 100)->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('agent_id')->references('id')->on('ent_entities')->nullOnDelete();
            $table->foreign('previous_policy_id')->references('id')->on('pol_policies')->nullOnDelete();
            $table->foreign('original_policy_id')->references('id')->on('pol_policies')->nullOnDelete();
            $table->index('policy_number');
            $table->index('quotation_id');
            $table->index('insurance_branch_id');
            $table->index('customer_id');
            $table->index('agent_id');
            $table->index('status');
            $table->index('previous_policy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pol_policies');
    }
};
