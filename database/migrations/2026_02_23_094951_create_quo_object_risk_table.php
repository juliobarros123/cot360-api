<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quo_object_risk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_version_id')->constrained('quo_quotation_versions')->cascadeOnDelete();
            $table->foreignId('object_id')->constrained('obj_objects')->restrictOnDelete();
            $table->foreignId('coverage_id')->constrained('cfg_coverages')->restrictOnDelete();

            $table->foreignId('object_risk_capital_id')->nullable()->constrained('quo_object_risk_capitals')->nullOnDelete();
            // Capitais para esta versão
            $table->decimal('dp_capital', 20, 2)->nullable();
            $table->decimal('rc_capital', 20, 2)->nullable();
            $table->decimal('building_capital', 20, 2)->nullable();
            $table->decimal('content_capital', 20, 2)->nullable();
            $table->decimal('exploration_capital', 20, 2)->nullable();
            $table->decimal('capital', 20, 2)->nullable();
            // Prémio
            $table->decimal('coverage_perc', 20, 6)->default(0);
            $table->double('applied_rate');
            $table->decimal('coverage_premium', 20, 2)->default(0);
            $table->string('rate_origin', 100)->nullable();
            $table->unsignedBigInteger('rate_id')->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('surcharge_percent', 5, 2)->default(0);
            $table->decimal('manual_rate', 20, 6)->nullable();
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index('quotation_version_id');
            $table->index('object_id');
            $table->index('coverage_id');
            $table->unique(['quotation_version_id', 'object_id', 'coverage_id'], 'quo_obj_risk_version_obj_cov_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quo_object_risk');
    }
};
