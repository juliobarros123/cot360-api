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
        Schema::create('cfg_rates_health', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_branch_id')->nullable()->constrained('cfg_insurance_branches')->nullOnDelete();
            $table->foreignId('coverage_id')->nullable()->constrained('cfg_coverages')->nullOnDelete();
            $table->integer('age_min')->nullable();
            $table->integer('age_max')->nullable();
            $table->string('gender', 10)->nullable()->comment('M, F, Any');
            $table->string('type', 50)->nullable();
            $table->string('package', 50)->nullable()->comment('Basic, Standard, Premium');
            $table->decimal('premium_value', 18, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->string('slug', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index('insurance_branch_id');
            $table->index('coverage_id');
            $table->index(['age_min', 'age_max']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfg_rates_health');
    }
};
