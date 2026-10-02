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
        Schema::create('pol_object_risk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_version_id')->constrained('pol_policy_versions')->cascadeOnDelete();
            $table->foreignId('object_id')->constrained('obj_objects')->restrictOnDelete();
            $table->foreignId('coverage_id')->constrained('cfg_coverages')->restrictOnDelete();
            $table->unsignedBigInteger('source_quo_object_risk_id')->nullable()->comment('Origem na cotação');
            $table->decimal('dp_capital', 18, 2)->nullable();
            $table->decimal('rc_capital', 18, 2)->nullable();
            $table->decimal('building_capital', 18, 2)->nullable();
            $table->decimal('content_capital', 18, 2)->nullable();
            $table->decimal('exploration_capital', 18, 2)->nullable();
            $table->decimal('capital', 18, 2)->nullable();
            $table->double('coverage_perc');
            $table->double('applied_rate');
            $table->double('coverage_premium');
            $table->string('rate_origin', 100)->nullable();
            $table->unsignedBigInteger('rate_id')->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('surcharge_percent', 5, 2)->default(0);
            $table->enum('status', ['Active', 'Suspended', 'Removed'])->default('Active');
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('slug', 100)->unique();
            $table->timestamp('created_at')->nullable();
            $table->foreign('source_quo_object_risk_id')->references('id')->on('quo_object_risk')->nullOnDelete();
            $table->index('policy_version_id');
            $table->index('object_id');
            $table->index('coverage_id');
            $table->index('status');
            $table->index('source_quo_object_risk_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pol_object_risk');
    }
};
